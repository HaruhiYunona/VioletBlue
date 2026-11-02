<?php
declare(strict_types=1);
namespace VioletBlue\service;

use Closure;
use RuntimeException;
use VioletBlue\support\{support_config, support_github, support_update_package};

/** 管理一次性更新任务、文件备份及失败恢复，不运行数据库迁移。 */
final class service_update
{
    private readonly service_release $releases;
    private readonly array $context;

    /** 用途：注入客户端、站点及写入观察器；入参：客户端、根目录、文件更新回调；返回值：更新服务。 */
    public function __construct(private readonly support_github $client = new support_github(), ?string $root = null, private readonly ?Closure $afterWrite = null)
    {
        $this->releases = new service_release($client, $root);
        $this->context = $this->releases->context();
    }

    /** 用途：生成受限任务路径；入参：任务标识；返回值：任务目录。 */
    private function directory(string $id): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $id)) throw new \InvalidArgumentException('无效的更新任务');
        return $this->context['storage'] . '/' . $id;
    }

    /** 用途：读取任务记录；入参：任务标识；返回值：内部任务。 */
    private function read(string $id): array
    {
        $path = $this->directory($id) . '/job.json';
        if (!is_file($path)) throw new \OutOfBoundsException('更新任务不存在');
        return json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
    }

    /** 用途：持久化任务进度；入参：任务及新字段；返回值：更新后的内部任务。 */
    private function write(array $job, array $changes = []): array
    {
        $job = [...$job, ...$changes, 'updated_at' => time()];
        support_config::atomic($this->directory($job['id']) . '/job.json', json_encode($job, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $job;
    }

    /** 用途：过滤前端可见的任务字段；入参：内部任务；返回值：公开进度。 */
    private function publicJob(array $job): array
    {
        return array_intersect_key($job, array_flip(['id', 'version', 'status', 'stage', 'progress', 'downloaded', 'size', 'message', 'updated_at', 'backup_available']));
    }

    /** 用途：串行执行更新或恢复；入参：回调；返回值：回调结果或锁忙时的 null。 */
    private function locked(Closure $callback): mixed
    {
        $lock = fopen($this->context['storage'] . '/update.lock', 'c');
        if ($lock === false) throw new RuntimeException('无法创建更新锁');
        try { return flock($lock, LOCK_EX | LOCK_NB) ? $callback() : null; }
        finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    /** 用途：创建服务器更新任务；入参：期望版本；返回值：公开任务进度。 */
    public function start(string $version): array
    {
        $result = $this->locked(function () use ($version): array {
            $activePath = $this->context['storage'] . '/active.json';
            if (is_file($activePath)) {
                $active = json_decode((string) file_get_contents($activePath), true);
                if (is_array($active) && is_string($active['id'] ?? null)) {
                    $job = $this->read($active['id']);
                    if (in_array($job['status'], ['queued', 'running'], true)) throw new \DomainException('已有更新任务，请先查看其进度');
                }
            }
            $check = $this->releases->check(true);
            if (!$check['can_update']) throw new \DomainException($check['reason']);
            if ($check['latest']['version'] !== $version) throw new \DomainException('发布版本已变化，请重新检查更新');
            $asset = $check['latest']['assets']['patch'] ?? $check['latest']['assets']['install'];
            if ($asset['size'] <= 0 || $asset['size'] > support_github::MAX_BYTES) throw new RuntimeException('更新包大小超出支持范围');
            $id = bin2hex(random_bytes(16));
            if (!mkdir($this->directory($id), 0700)) throw new RuntimeException('无法创建更新任务');
            $job = $this->write(['id' => $id, 'version' => $version, 'from' => $this->context['version'], 'asset' => $asset,
                'repository' => $this->context['repository'], 'status' => 'queued', 'stage' => 'queued', 'progress' => 0,
                'downloaded' => 0, 'size' => $asset['size'], 'message' => '更新任务已创建', 'backup_available' => false]);
            support_config::atomic($activePath, json_encode(['id' => $id], JSON_THROW_ON_ERROR));
            return $this->publicJob($job);
        });
        if ($result === null) throw new \DomainException('已有更新正在执行');
        return $result;
    }

    /** 用途：查询最新或指定任务，接管中断任务；入参：可选任务标识；返回值：公开进度或 null。 */
    public function status(string $id = ''): ?array
    {
        if ($id === '') {
            $path = $this->context['storage'] . '/active.json';
            $active = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            if (!is_array($active) || !is_string($active['id'] ?? null)) return null;
            $id = $active['id'];
        }
        $job = $this->read($id);
        if (in_array($job['status'], ['queued', 'running'], true)) $this->process($id);
        return $this->publicJob($this->read($id));
    }

    /** 用途：原子替换目标文件；入参：来源和目标路径；返回值：无。 */
    private function replace(string $source, string $target): void
    {
        $parent = dirname($target);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) throw new RuntimeException('无法创建程序目录');
        $temporary = tempnam($parent, '.vb-update-');
        if ($temporary === false) throw new RuntimeException('无法暂存待替换文件');
        try {
            if (!copy($source, $temporary)) throw new RuntimeException('无法写入更新文件');
            $mode = is_file($target) ? fileperms($target) & 0777 : 0644;
            if (!chmod($temporary, $mode) || !rename($temporary, $target)) throw new RuntimeException('无法替换程序文件，请检查目录权限');
            if (str_ends_with($target, '.php') && function_exists('opcache_invalidate')) @opcache_invalidate($target, true);
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    /** 用途：按备份清单恢复文件；入参：任务目录；返回值：无。 */
    private function restore(string $directory): void
    {
        $path = $directory . '/journal.json';
        if (!is_file($path)) return;
        $journal = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        foreach (array_reverse($journal) as $record) {
            $target = support_update_package::target($this->context['root'], $record['path']);
            if ($record['existed']) $this->replace($directory . '/backup/' . $record['path'], $target);
            elseif (is_file($target) && !unlink($target)) throw new RuntimeException('无法移除更新新增文件');
        }
    }

    /** 用途：下载、校验、备份并应用更新，失败时恢复；入参：任务标识；返回值：无。 */
    public function process(string $id): void
    {
        $this->locked(function () use ($id): void {
            $job = $this->read($id);
            if (!in_array($job['status'], ['queued', 'running'], true)) return;
            $directory = $this->directory($id);
            $maintenance = $this->context['storage'] . '/maintenance.json';
            ignore_user_abort(true); set_time_limit(0);
            try {
                if ($job['status'] === 'running') {
                    $this->restore($directory);
                    $this->write($job, ['status' => 'failed', 'stage' => 'restored', 'message' => '上次更新中断，已恢复原程序，请重新检查更新']);
                    return;
                }
                if ($job['repository'] !== $this->context['repository'] || $job['from'] !== $this->context['version']) throw new RuntimeException('站点版本或更新仓库已变化，请重新创建任务');
                $job = $this->write($job, ['status' => 'running', 'stage' => 'download', 'message' => '正在下载更新包']);
                $lastProgress = 0.0;
                $this->client->request($job['asset']['url'], $directory . '/package.zip', function (int $bytes) use (&$job, &$lastProgress): void {
                    if (microtime(true) - $lastProgress < 0.3) return;
                    $lastProgress = microtime(true);
                    $job = $this->write($job, ['downloaded' => $bytes, 'progress' => min(40, (int) ($bytes / max(1, $job['size']) * 40))]);
                });
                $archive = $directory . '/package.zip';
                if (filesize($archive) !== $job['size'] || !hash_equals($job['asset']['sha256'], hash_file('sha256', $archive))) throw new RuntimeException('更新包大小或 SHA256 校验失败，未替换程序文件');
                $job = $this->write($job, ['downloaded' => $job['size'], 'progress' => 45, 'stage' => 'validate', 'message' => '正在检查更新包']);
                $files = support_update_package::stage($archive, $directory . '/stage', $job['version']);
                $installed = json_decode((string) file_get_contents($this->context['root'] . '/.package.json'), true);
                $present = array_fill_keys(array_map('strtolower', $files), true);
                $obsolete = [];
                foreach (is_array($installed['files'] ?? null) ? $installed['files'] : [] as $path) {
                    if (!is_string($path) || isset($present[strtolower($path)]) || str_starts_with(strtolower($path), 'build/') || support_update_package::protectedPath($path) || !support_update_package::allowed($path)) continue;
                    if (is_file(support_update_package::target($this->context['root'], $path))) $obsolete[] = $path;
                }
                $obsolete = array_values(array_unique($obsolete));
                $operations = [...array_values(array_diff($files, ['.package.json'])), ...$obsolete, '.package.json'];
                $requiredSpace = 10 * 1024 * 1024;
                foreach ($operations as $path) {
                    $target = support_update_package::target($this->context['root'], $path);
                    $staged = $directory . '/stage/' . $path;
                    $requiredSpace += (is_file($staged) ? filesize($staged) * 2 : 0) + (is_file($target) ? filesize($target) : 0);
                }
                $freeSpace = @disk_free_space($this->context['root']);
                if ($freeSpace !== false && $freeSpace < $requiredSpace) throw new RuntimeException('磁盘可用空间不足，未替换程序文件');
                $job = $this->write($job, ['progress' => 50, 'stage' => 'backup', 'message' => '正在备份程序文件']);
                $journal = [];
                foreach ($operations as $path) {
                    $target = support_update_package::target($this->context['root'], $path);
                    if (file_exists($target) && !is_file($target)) throw new RuntimeException('程序文件与现有目录冲突：' . $path);
                    $parent = dirname($target);
                    while (!file_exists($parent)) $parent = dirname($parent);
                    if (!is_writable($parent) || is_file($target) && !is_readable($target)) throw new RuntimeException('程序目录权限不足：' . $path);
                    $exists = is_file($target);
                    if ($exists) {
                        $backup = $directory . '/backup/' . $path;
                        if (!is_dir(dirname($backup)) && !mkdir(dirname($backup), 0700, true) && !is_dir(dirname($backup))) throw new RuntimeException('无法创建程序备份');
                        if (!copy($target, $backup)) throw new RuntimeException('无法备份程序文件');
                    }
                    $journal[] = ['path' => $path, 'existed' => $exists];
                }
                support_config::atomic($directory . '/journal.json', json_encode($journal, JSON_THROW_ON_ERROR));
                $job = $this->write($job, ['backup_available' => true, 'stage' => 'install', 'progress' => 60, 'message' => '正在应用更新']);
                support_config::atomic($maintenance, json_encode(['id' => $id, 'expires' => time() + 600], JSON_THROW_ON_ERROR));
                foreach ($operations as $index => $path) {
                    $target = support_update_package::target($this->context['root'], $path);
                    if (in_array($path, $obsolete, true)) {
                        if (!unlink($target)) throw new RuntimeException('无法移除旧程序文件');
                        if (str_ends_with($target, '.php') && function_exists('opcache_invalidate')) @opcache_invalidate($target, true);
                    } else $this->replace($directory . '/stage/' . $path, $target);
                    if ($this->afterWrite !== null) ($this->afterWrite)($path);
                    $job = $this->write($job, ['progress' => 60 + (int) (($index + 1) / count($operations) * 39)]);
                }
                $this->write($job, ['status' => 'completed', 'stage' => 'completed', 'progress' => 100, 'message' => '更新成功，请刷新页面加载新版本']);
            } catch (\Throwable $error) {
                error_log('自动更新失败：' . $error->getMessage());
                try {
                    $this->restore($directory);
                    $this->write($job, ['status' => 'failed', 'stage' => 'restored', 'message' => $error->getMessage() . (is_file($directory . '/journal.json') ? '；已恢复原程序' : '')]);
                } catch (\Throwable $restoreError) {
                    error_log('更新恢复失败：' . $restoreError->getMessage());
                    $this->write($job, ['status' => 'failed', 'stage' => 'restore_failed', 'message' => '自动恢复失败，请使用服务器 app/storage/updates/' . $id . '/backup 及 journal.json 恢复程序']);
                }
            } finally {
                $final = $this->read($id);
                if (($final['stage'] ?? '') !== 'restore_failed' && is_file($maintenance)) unlink($maintenance);
            }
        });
    }
}
