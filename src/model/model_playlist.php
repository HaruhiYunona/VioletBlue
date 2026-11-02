<?php
declare(strict_types=1);
namespace VioletBlue\model;

use PDO;
use PDOStatement;
use Throwable;

/** 执行歌曲和站点选项的参数化查询及事务。 */
final class model_playlist
{
    private bool $capacityChecked = false;

    /**
     * 将旧选项值字段扩容为 TEXT。
     * @return void 无
     */
    public function ensureOptionCapacity(): void
    {
        $pdo = model_database::connection();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') return;
        $statement = $pdo->prepare('SELECT DATA_TYPE FROM information_schema.columns WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $statement->execute([trim(model_database::table('others'), '`'), 'value']);
        if (in_array($statement->fetchColumn(), ['text', 'mediumtext', 'longtext'], true)) return;
        try { $pdo->exec('ALTER TABLE ' . model_database::table('others') . ' MODIFY value TEXT NOT NULL'); }
        catch (\PDOException $error) { throw new \RuntimeException('设置字段扩容失败，请确认数据库账号具有 ALTER 权限后重试', 503, $error); }
    }

    /**
     * 将旧歌曲字段扩容并补齐组合唯一索引。
     * @return void 无
     */
    public function ensureSongCapacity(): void
    {
        if ($this->capacityChecked) return;
        $pdo = model_database::connection();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $table = trim(model_database::table('song'), '`');
            $statement = $pdo->prepare('SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN (?, ?)');
            $statement->execute([$table, 'name', 'singer']);
            $changes = [];
            foreach ($statement->fetchAll() as $column) {
                if ((int) $column['CHARACTER_MAXIMUM_LENGTH'] < 300) {
                    $changes[] = 'MODIFY `' . $column['COLUMN_NAME'] . '` VARCHAR(300) NOT NULL';
                }
            }
            $indexes = [];
            foreach ($pdo->query('SHOW INDEX FROM ' . model_database::table('song'))->fetchAll() as $index) {
                if ((int) $index['Non_unique'] === 0 && $index['Sub_part'] === null) $indexes[$index['Key_name']][(int) $index['Seq_in_index']] = $index['Column_name'];
            }
            $identity = false;
            foreach ($indexes as $columns) { ksort($columns); if (array_values($columns) === ['name', 'singer']) $identity = true; }
            if (!$identity) $changes[] = 'ADD UNIQUE KEY song_identity (name,singer)';
            if ($changes !== []) {
                try {
                    $pdo->exec('ALTER TABLE ' . model_database::table('song') . ' ROW_FORMAT=DYNAMIC, ' . implode(', ', $changes));
                } catch (\PDOException $error) {
                    if (($error->errorInfo[1] ?? 0) === 1062) throw new \RuntimeException('歌单中存在重复歌名和歌手，请先处理重复数据后重试结构升级', 503, $error);
                    throw new \RuntimeException('歌名和歌手字段扩容失败，请确认数据库账号具有 ALTER 权限后重试', 503, $error);
                }
            }
        }
        $this->capacityChecked = true;
    }
    /**
     * 预处理 SQL 并按值类型绑定参数。
     * @param string $sql 含占位符的 SQL
     * @param array $params 位置或命名参数
     * @return PDOStatement 已执行的结果游标
     */
    private function run(string $sql, array $params = []): PDOStatement
    {
        $statement = model_database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();
        return $statement;
    }

    /**
     * 读取单个站点选项。
     * @param string $name 选项名
     * @return string 选项值，不存在时为空字符串
     */
    public function option(string $name): string
    {
        $value = $this->run('SELECT value FROM ' . model_database::table('others') . ' WHERE name = ?', [$name])->fetchColumn();
        return is_string($value) ? $value : '';
    }

    /**
     * 更新已有站点选项。
     * @param string $name 选项名
     * @param string $value 新值
     * @return void 无
     */
    public function setOption(string $name, string $value): void
    {
        $this->run('UPDATE ' . model_database::table('others') . ' SET value = ? WHERE name = ?', [$value, $name]);
    }

    /**
     * 按分类和页码读取歌曲，每页 25 首。
     * @param string $tag 分类名，全部表示不筛选
     * @param int $page 页码，从 1 开始
     * @return array 歌曲数据库记录
     */
    public function list(string $tag, int $page): array
    {
        $sql = 'SELECT * FROM ' . model_database::table('song') . ' AS songs';
        $params = [];
        if ($tag !== '全部') {
            $driver = model_database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
            $jsonMatch = $driver === 'sqlite' ? 'EXISTS (SELECT 1 FROM json_each(songs.type) WHERE value = ?)' : 'JSON_CONTAINS(songs.type, JSON_QUOTE(?))';
            $sql .= " WHERE CASE WHEN JSON_VALID(songs.type) THEN " . $jsonMatch . " ELSE songs.type LIKE ? ESCAPE '!' END";
            $params = [$tag, '%|' . $this->like($tag) . '|%'];
        }
        $sql .= ' ORDER BY uid ASC LIMIT ? OFFSET ?';
        return $this->run($sql, [...$params, 25, ($page - 1) * 25])->fetchAll();
    }

    /**
     * 转义 LIKE 查询中的通配符。
     * @param string $value 原始搜索文本
     * @return string 使用 ! 转义的文本
     */
    private function like(string $value): string
    {
        return strtr($value, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }

    /**
     * 按歌名、歌手、冠名或编号查询歌曲。
     * @param string $search 搜索文本
     * @return array 匹配的数据库记录
     */
    public function search(string $search): array
    {
        $term = '%' . $this->like($search) . '%';
        return $this->run('SELECT * FROM ' . model_database::table('song') . " WHERE name LIKE ? ESCAPE '!' OR singer LIKE ? ESCAPE '!' OR note LIKE ? ESCAPE '!' OR CAST(uid AS CHAR) = ? ORDER BY uid", [$term, $term, $term, $search])->fetchAll();
    }

    /**
     * 随机读取一首歌曲。
     * @return array 零条或一条数据库记录
     */
    public function random(): array
    {
        $function = model_database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'RANDOM()' : 'RAND()';
        return $this->run('SELECT * FROM ' . model_database::table('song') . ' ORDER BY ' . $function . ' LIMIT 1')->fetchAll();
    }

    /**
     * 按编号读取歌曲。
     * @param int $uid 歌曲编号
     * @return ?array 歌曲记录，不存在时为 null
     */
    public function detail(int $uid): ?array
    {
        $row = $this->run('SELECT * FROM ' . model_database::table('song') . ' WHERE uid = ?', [$uid])->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * 检查相同歌名和歌手是否已存在。
     * @param string $name 歌名
     * @param string $singer 歌手
     * @param int $exclude 查重时排除的歌曲编号，0 表示不排除
     * @return bool 是否重复
     */
    public function duplicate(string $name, string $singer, int $exclude = 0): bool
    {
        return $this->run('SELECT uid FROM ' . model_database::table('song') . ' WHERE name = ? AND singer = ? AND uid <> ? LIMIT 1', [$name, $singer, $exclude])->fetchColumn() !== false;
    }

    /**
     * 插入已校验的歌曲。
     * @param array $song 包含 name、singer、type、note 的歌曲数据
     * @return void 无
     */
    public function add(array $song): void
    {
        $this->run('INSERT INTO ' . model_database::table('song') . ' (name,singer,type,note,time) VALUES (?,?,?,?,?)', [$song['name'], $song['singer'], $song['type'], $song['note'], time()]);
    }

    /**
     * 更新指定歌曲。
     * @param int $uid 歌曲编号
     * @param array $song 包含 name、singer、type、note 的歌曲数据
     * @return void 无
     */
    public function edit(int $uid, array $song): void
    {
        $this->run('UPDATE ' . model_database::table('song') . ' SET name=?,singer=?,type=?,note=? WHERE uid=?', [$song['name'], $song['singer'], $song['type'], $song['note'], $uid]);
    }

    /**
     * 删除指定歌曲。
     * @param int $uid 歌曲编号
     * @return bool 是否删除了记录
     */
    public function delete(int $uid): bool
    {
        return $this->run('DELETE FROM ' . model_database::table('song') . ' WHERE uid=?', [$uid])->rowCount() > 0;
    }

    /**
     * 清空当前站点歌曲并重置自增序列。
     * @return void 无
     */
    public function clear(): void
    {
        $table = model_database::table('song');
        if (model_database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            // TRUNCATE 同时清空和重置序列，DDL 不放入事务。
            $this->run('TRUNCATE TABLE ' . $table);
            return;
        }
        $this->transaction(function () use ($table): void {
            $this->run('DELETE FROM ' . $table);
            $this->run('DELETE FROM sqlite_sequence WHERE name = ?', [trim($table, '`')]);
        });
    }

    /**
     * 批量写入前端已校验歌曲，唯一键冲突时保留已有记录。
     * @param array $songs 完整歌曲数据列表
     * @return int 本批新增数量
     */
    public function importRows(array $songs): int
    {
        if ($songs === []) return 0;
        $values = []; $params = [];
        foreach ($songs as $song) {
            $values[] = '(?,?,?,?,?)';
            array_push($params, $song['name'], $song['singer'], json_encode($song['tags'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $song['claim'], time());
        }
        $sqlite = model_database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $sql = 'INSERT INTO ' . model_database::table('song') . ' (name,singer,type,note,time) VALUES ' . implode(',', $values)
            . ($sqlite ? ' ON CONFLICT(name,singer) DO NOTHING' : ' ON DUPLICATE KEY UPDATE uid=uid');
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * 在事务中执行回调，异常时回滚。
     * @param callable $action 执行数据库写入的回调
     * @return void 无
     */
    public function transaction(callable $action): void
    {
        $pdo = model_database::connection();
        $pdo->beginTransaction();
        try {
            $action();
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
