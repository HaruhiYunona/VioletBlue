<?php
declare(strict_types=1);
namespace VioletBlue\service;

use InvalidArgumentException;
use VioletBlue\model\model_playlist;
use VioletBlue\support\support_input;

/** 校验歌曲字段，处理查询、去重和批量导入。 */
final class service_playlist
{
    public const CLEAR_CONFIRMATION = '我已经知道后果，并决定如此';
    /**
     * 创建歌曲业务服务。
     * @param model_playlist $model 歌曲数据访问对象
     */
    public function __construct(private readonly model_playlist $model = new model_playlist()) {}

    /**
     * 合并全部分类并去除重复标签。
     * @return array 分类文本列表
     */
    public function tags(): array
    {
        return array_values(array_unique(['全部', ...self::decodeTags($this->model->option('type'))]));
    }

    /**
     * 读取 JSON 标签或旧竖线编码。
     * @param string $value 数据库标签文本
     * @return array 标签列表
     */
    public static function decodeTags(string $value): array
    {
        $tags = json_decode($value, true);
        if (is_array($tags) && array_is_list($tags)) {
            return array_values(array_filter($tags, static fn(mixed $tag): bool => is_string($tag) && $tag !== ''));
        }
        return array_values(array_filter(explode('||', trim($value, '|')), static fn(string $tag): bool => $tag !== ''));
    }

    /**
     * 读取分页歌曲并转换为接口字段。
     * @param string $tag 分类名
     * @param int $page 从 1 开始的页码
     * @return array 歌曲列表
     */
    public function list(string $tag, int $page): array
    {
        return $this->format($this->model->list($tag, $page));
    }

    /**
     * 校验搜索长度并查询歌曲。
     * @param string $search 最长 600 字的搜索文本
     * @return array 匹配的歌曲列表
     */
    public function search(string $search): array
    {
        if (mb_strlen($search) > 600) {
            throw new InvalidArgumentException('搜索内容过长');
        }
        return $this->format($this->model->search($search));
    }

    /**
     * 读取一首随机歌曲。
     * @return array 零首或一首歌曲列表
     */
    public function random(): array
    {
        return $this->format($this->model->random());
    }

    /**
     * 将数据库字段转换为前端歌曲结构。
     * @param array $rows 数据库歌曲记录
     * @return array 含 uid、song、singer、type、notice 的列表
     */
    private function format(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'uid' => (int) $row['uid'], 'song' => $row['name'], 'singer' => $row['singer'],
            'type' => self::decodeTags($row['type']), 'notice' => $row['note'],
        ], $rows);
    }

    /**
     * 读取用于编辑的歌曲字段。
     * @param int $uid 歌曲编号
     * @return array 含 name、singer、tags、claim 的歌曲
     */
    public function detail(int $uid): array
    {
        $row = $this->model->detail($uid);
        if ($row === null) {
            throw new \OutOfBoundsException('歌曲不存在');
        }
        return ['name' => $row['name'], 'singer' => $row['singer'], 'tags' => self::decodeTags($row['type']), 'claim' => $row['note']];
    }

    /**
     * 校验歌曲长度并编码分类标签。
     * @param array $data 歌曲表单字段
     * @return array 可入库的歌曲数据
     */
    public function prepare(array $data): array
    {
        $name = trim(support_input::text($data, 'name'));
        $singer = trim(support_input::text($data, 'singer'));
        $note = trim(support_input::text($data, 'claim'));
        if (mb_strlen($name) < 1 || mb_strlen($name) > 300 || mb_strlen($singer) < 1 || mb_strlen($singer) > 300 || mb_strlen($note) > 5000) {
            throw new InvalidArgumentException('歌名和歌手须为1至300字，冠名最长5000字');
        }
        foreach ([$name, $singer, $note] as $text) {
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text)) throw new InvalidArgumentException('歌曲内容不能包含不可见控制字符');
        }
        $tags = self::normalizeTags(support_input::array($data, 'tags'));
        if ($tags === []) {
            throw new InvalidArgumentException('至少填写一个标签');
        }
        return ['name' => $name, 'singer' => $singer, 'type' => json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'note' => $note];
    }

    /**
     * 校验标签列表并去重、过滤保留分类。
     * @param array $tags 原始标签列表
     * @return array 规范化标签列表
     */
    public static function normalizeTags(array $tags): array
    {
        if (!array_is_list($tags) || count($tags) > 100) {
            throw new InvalidArgumentException('标签必须为列表，最多100个');
        }
        $result = [];
        foreach ($tags as $tag) {
            if (!is_string($tag) || !mb_check_encoding($tag, 'UTF-8') || mb_strlen($tag) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $tag)) {
                throw new InvalidArgumentException('每个标签最长100字，不能包含不可见控制字符');
            }
            $tag = trim($tag);
            if ($tag !== '' && $tag !== '全部') {
                $result[] = $tag;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * 校验并新增不重复的歌曲。
     * @param array $data 歌曲表单字段
     * @return bool 是否新增成功
     */
    public function add(array $data): bool
    {
        $song = $this->prepare($data);
        $this->assertUnique($song);
        $this->model->ensureSongCapacity();
        $this->model->add($song);
        return true;
    }

    /**
     * 检查歌曲是否与其他记录重复。
     * @param array $song 包含 name、singer 的歌曲
     * @param int $exclude 排除的歌曲编号
     * @return void 无，重复时抛出异常
     */
    private function assertUnique(array $song, int $exclude = 0): void
    {
        if ($this->model->duplicate($song['name'], $song['singer'], $exclude)) {
            throw new \DomainException('歌曲已存在：' . $song['name'] . ' - ' . $song['singer']);
        }
    }

    /**
     * 批量导入歌曲，任一行失败时全部回滚。
     * @param array $songs 完整歌曲表单列表
     * @return string 导入成功提示
     */
    public function batch(array $songs): string
    {
        if (!array_is_list($songs) || count($songs) < 1) {
            throw new InvalidArgumentException('歌曲列表不能为空');
        }
        // DDL 会隐式提交，字段扩容必须在批量事务开始之前完成。
        $this->model->ensureSongCapacity();
        $this->model->transaction(function () use ($songs): void {
            foreach ($songs as $song) {
                if (!is_array($song)) {
                    throw new InvalidArgumentException('歌曲行必须为对象');
                }
                $this->add($song);
            }
        });
        return '成功入库歌曲，共计：' . count($songs) . '首';
    }

    /**
     * 校验目标与重复项后更新歌曲。
     * @param int $uid 歌曲编号
     * @param array $data 歌曲表单字段
     * @return bool 是否修改成功
     */
    public function edit(int $uid, array $data): bool
    {
        $this->detail($uid);
        $song = $this->prepare($data);
        $this->assertUnique($song, $uid);
        $this->model->ensureSongCapacity();
        $this->model->edit($uid, $song);
        return true; // 提交相同内容也属于成功。
    }

    /**
     * 校验确认文字后取消未完成导入并清空歌单。
     * @param string $confirmation 用户输入的完整确认文字
     * @return bool 是否清空成功
     */
    public function clear(string $confirmation): bool
    {
        if ($confirmation !== self::CLEAR_CONFIRMATION) {
            throw new InvalidArgumentException('确认文字不正确，请完整输入“' . self::CLEAR_CONFIRMATION . '”');
        }
        \VioletBlue\support\support_song_lock::run(function (): void {
            // 先取消尚未完成的导入，再删除歌曲，后台任务不会继续补写。
            (new service_import())->cancelAll();
            $this->model->clear();
        });
        return true;
    }

    /**
     * 删除指定歌曲，不存在时抛出异常。
     * @param int $uid 歌曲编号
     * @return bool 是否删除成功
     */
    public function delete(int $uid): bool
    {
        if (!$this->model->delete($uid)) {
            throw new \OutOfBoundsException('歌曲不存在');
        }
        return true;
    }
}
