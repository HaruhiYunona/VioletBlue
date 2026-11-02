<?php
declare(strict_types=1);
namespace VioletBlue\service;

use VioletBlue\model\model_playlist;
use VioletBlue\support\{support_config, support_song_lock};

/** 接收整份歌曲数据，管理后台批量入库、进度和取消。 */
final class service_import
{
    private const BATCH_SIZE = 250;

    /**
     * 解析当前站点的私有任务目录。
     * @return string 任务目录
     */
    private function directory(): string
    {
        $config = support_config::read('database');
        $scope = hash('sha256', json_encode([$config['host'], $config['port'], $config['database'], $config['prefix']], JSON_THROW_ON_ERROR));
        $directory = support_config::root() . '/storage/imports/' . substr($scope, 0, 20);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('无法创建导入目录');
        return $directory;
    }

    /**
     * 校验任务标识并生成文件路径。
     * @param string $id 随机任务标识
     * @param string $suffix 文件后缀
     * @return string 私有文件路径
     */
    private function path(string $id, string $suffix): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $id)) throw new \InvalidArgumentException('无效的导入任务');
        return $this->directory() . '/' . $id . '.' . $suffix;
    }

    /**
     * 读取任务元数据。
     * @param string $id 任务标识
     * @return array 任务状态
     */
    private function read(string $id): array
    {
        $path = $this->path($id, 'json');
        if (!is_file($path)) throw new \OutOfBoundsException('导入任务不存在或已过期');
        return json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    }

    /**
     * 保存任务状态和更新时间。
     * @param array $job 任务状态
     * @return void
     */
    private function write(array $job): void
    {
        $job['updated'] = time();
        support_config::atomic($this->path($job['id'], 'json'), json_encode($job, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * 返回前端使用的进度字段。
     * @param array $job 内部任务状态
     * @return array 对外进度
     */
    private function progress(array $job): array
    {
        return array_intersect_key($job, array_flip(['id', 'status', 'total', 'processed', 'imported', 'skipped', 'message']));
    }

    /**
     * 接收一次完整提交，只校验数据协议并写入私有任务文件。
     * @param array $songs 前端已预览和校验的完整歌曲列表
     * @return array 新任务进度
     */
    public function start(array $songs): array
    {
        if (!array_is_list($songs) || $songs === []) throw new \InvalidArgumentException('导入数据必须为非空歌曲列表');
        return support_song_lock::run(function () use ($songs): array {
            $this->cleanup();
            $id = bin2hex(random_bytes(16));
            $path = $this->path($id, 'rows');
            $file = fopen($path, 'xb');
            if ($file === false) throw new \RuntimeException('无法保存导入数据');
            try {
                foreach ($songs as $song) {
                    if (!is_array($song) || !isset($song['name'], $song['singer'], $song['tags'], $song['claim'])
                        || !is_string($song['name']) || !is_string($song['singer']) || !is_string($song['claim'])
                        || !is_array($song['tags']) || !array_is_list($song['tags'])) throw new \InvalidArgumentException('导入数据格式不正确');
                    foreach ($song['tags'] as $tag) if (!is_string($tag)) throw new \InvalidArgumentException('标签数据格式不正确');
                    $line = json_encode(array_intersect_key($song, array_flip(['name', 'singer', 'tags', 'claim'])), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
                    if (fwrite($file, $line) !== strlen($line)) throw new \RuntimeException('导入数据写入失败');
                }
            } catch (\Throwable $error) {
                fclose($file); unlink($path); throw $error;
            }
            fclose($file);
            $job = ['id' => $id, 'status' => 'queued', 'total' => count($songs), 'processed' => 0, 'imported' => 0, 'skipped' => 0, 'offset' => 0, 'message' => '', 'updated' => time()];
            $this->write($job);
            return $this->progress($job);
        });
    }

    /**
     * 执行一个入库批次并保存读取偏移量。
     * @param string $id 任务标识
     * @return array 最新进度
     */
    private function step(string $id): array
    {
        return support_song_lock::run(function () use ($id): array {
            $job = $this->read($id);
            if (!in_array($job['status'], ['queued', 'running'], true)) return $this->progress($job);
            $file = null;
            try {
                $file = fopen($this->path($id, 'rows'), 'rb');
                if ($file === false || fseek($file, $job['offset']) !== 0) throw new \RuntimeException('导入数据读取失败');
                $rows = [];
                while (count($rows) < self::BATCH_SIZE && ($line = fgets($file)) !== false) $rows[] = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
                if ($rows === [] && $job['processed'] < $job['total']) throw new \RuntimeException('导入数据不完整');
                $model = new model_playlist();
                $model->ensureSongCapacity();
                $inserted = 0;
                $model->transaction(function () use ($model, $rows, &$inserted): void { $inserted = $model->importRows($rows); });
                $job['offset'] = ftell($file); $job['processed'] += count($rows); $job['imported'] += $inserted; $job['skipped'] += count($rows) - $inserted;
                $job['status'] = $job['processed'] >= $job['total'] ? 'completed' : 'running';
            } catch (\Throwable $error) {
                error_log('歌单导入失败：' . $error->getMessage());
                $job['status'] = 'failed';
                $job['message'] = $error->getCode() === 503 ? $error->getMessage() : '导入失败，已完成的歌曲保留，请检查数据和数据库状态后重试';
            } finally {
                if (is_resource($file)) fclose($file);
            }
            $this->write($job);
            if (in_array($job['status'], ['completed', 'failed'], true) && is_file($this->path($id, 'rows'))) unlink($this->path($id, 'rows'));
            return $this->progress($job);
        });
    }

    /**
     * 查询进度并推进可恢复批次，与后台进程共用写锁。
     * @param string $id 任务标识
     * @return array 最新进度
     */
    public function status(string $id): array
    {
        return $this->step($id);
    }

    /**
     * 在响应发送后连续处理服务器批次。
     * @param string $id 任务标识
     * @return void
     */
    public function process(string $id): void
    {
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) set_time_limit(0);
        do { $progress = $this->step($id); } while (in_array($progress['status'], ['queued', 'running'], true));
    }

    /**
     * 取消一个任务，保留已写入歌曲。
     * @param string $id 任务标识
     * @return array 取消后的进度
     */
    public function cancel(string $id): array
    {
        return support_song_lock::run(function () use ($id): array {
            $job = $this->read($id);
            if (in_array($job['status'], ['queued', 'running'], true)) {
                $job['status'] = 'cancelled'; $job['message'] = '导入已取消'; $this->write($job);
                if (is_file($this->path($id, 'rows'))) unlink($this->path($id, 'rows'));
            }
            return $this->progress($job);
        });
    }

    /** 取消当前站点未完成的任务；调用方须持有歌曲写锁；返回：无。 */
    public function cancelAll(): void
    {
        if (!is_file(support_config::path('database'))) return;
        foreach (glob($this->directory() . '/*.json') ?: [] as $path) {
            $job = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
            if (in_array($job['status'], ['queued', 'running'], true)) {
                $job['status'] = 'cancelled'; $job['message'] = '歌单已清空，导入任务已取消'; $this->write($job);
                if (is_file($this->path($job['id'], 'rows'))) unlink($this->path($job['id'], 'rows'));
            }
        }
    }

    /** 删除七天前的任务记录及数据；调用方须持锁；返回：无。 */
    private function cleanup(): void
    {
        foreach (glob($this->directory() . '/*.json') ?: [] as $path) {
            if (filemtime($path) >= time() - 604800) continue;
            $id = basename($path, '.json');
            $rows = $this->path($id, 'rows');
            if (is_file($rows)) unlink($rows);
            unlink($path);
        }
    }
}
