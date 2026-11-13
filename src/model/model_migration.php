<?php
declare(strict_types=1);
namespace VioletBlue\model;

use PDO;
use RuntimeException;
use Throwable;

/** 发现旧歌单表，读取迁移快照并替换升级后的数据表。 */
final class model_migration
{
    public function __construct(private readonly PDO $pdo) {}

    /** 列出当前账号可访问的歌单表对；返回：数据库及表前缀列表。 */
    public function candidates(): array
    {
        $rows = $this->pdo->query("SELECT s.TABLE_SCHEMA AS db,s.TABLE_NAME AS song FROM information_schema.tables s
            JOIN information_schema.tables o ON o.TABLE_SCHEMA=s.TABLE_SCHEMA AND o.TABLE_NAME=CONCAT(LEFT(s.TABLE_NAME,CHAR_LENGTH(s.TABLE_NAME)-5),'_others')
            WHERE RIGHT(s.TABLE_NAME,5)='_song' AND s.TABLE_TYPE='BASE TABLE' AND o.TABLE_TYPE='BASE TABLE'
            AND s.TABLE_SCHEMA NOT IN ('mysql','sys','performance_schema','information_schema') ORDER BY s.TABLE_SCHEMA,s.TABLE_NAME")->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $prefix = substr($row['song'], 0, -5);
            if (!preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,39}\z/D', $prefix)) continue;
            if (preg_match('/\Avb_(?:backup|migrate)_\d{14}_[a-f0-9]{8}\z/D', $prefix)) continue;
            try { $result[] = $this->target($row['db'], $prefix); }
            catch (\InvalidArgumentException|\PDOException) { continue; }
        }
        return $result;
    }

    /** 验证表结构并读取目标信息；入参：数据库、前缀；返回：目标表信息。 */
    public function target(string $database, string $prefix): array
    {
        model_database::validatePrefix($prefix);
        if ($database === '' || strlen($database) > 256 || preg_match('/[\x00-\x1f\x7f]/', $database)
            || in_array($database, ['mysql','sys','performance_schema','information_schema'], true)) throw new \InvalidArgumentException('数据库名称无效');
        foreach (['song' => ['uid','name','singer','type','note','time'], 'others' => ['name','value']] as $suffix => $required) {
            $statement = $this->pdo->prepare('SELECT COLUMN_NAME FROM information_schema.columns WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
            $statement->execute([$database, $prefix . '_' . $suffix]);
            if (array_diff($required, $statement->fetchAll(PDO::FETCH_COLUMN)) !== []) throw new \InvalidArgumentException('未找到完整的歌单数据表');
        }
        return ['database' => $database, 'prefix' => $prefix, 'song_table' => $prefix . '_song', 'others_table' => $prefix . '_others',
            'total' => (int) $this->pdo->query('SELECT COUNT(*) FROM ' . $this->table($database, $prefix . '_song'))->fetchColumn()];
    }

    /** 读取歌曲、设置与表结构；入参：已验证目标；返回：迁移快照。 */
    public function snapshot(array $target): array
    {
        $result = [];
        foreach (['song', 'others'] as $suffix) {
            $table = $this->table($target['database'], $target[$suffix . '_table']);
            $result[$suffix] = $this->pdo->query('SELECT * FROM ' . $table . ' ORDER BY ' . ($suffix === 'song' ? 'uid' : 'name'))->fetchAll();
            $result[$suffix . '_schema'] = $this->pdo->query('SHOW CREATE TABLE ' . $table)->fetch(PDO::FETCH_NUM)[1];
        }
        return $result;
    }

    /** 创建并校验新表，完成后一次替换；入参：目标、快照、处理后的歌曲；返回：原表备份名称。 */
    public function replace(array $target, array $snapshot, array $songs): array
    {
        $database = $target['database'];
        $token = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $temporary = []; $backups = [];
        foreach (['song', 'others'] as $suffix) {
            $temporary[$suffix] = 'vb_migrate_' . $token . '_' . $suffix;
            $backups[$suffix] = 'vb_backup_' . $token . '_' . $suffix;
        }
        try {
            foreach (['song', 'others'] as $suffix) {
                $this->pdo->exec('CREATE TABLE ' . $this->table($database, $temporary[$suffix]) . ' LIKE ' . $this->table($database, $target[$suffix . '_table']));
            }
            $songTable = $this->table($database, $temporary['song']);
            $othersTable = $this->table($database, $temporary['others']);
            $available = $this->pdo->query("SHOW COLLATION WHERE Collation IN ('utf8mb4_0900_bin','utf8mb4_nopad_bin','utf8mb4_bin')")->fetchAll(PDO::FETCH_COLUMN);
            $collation = in_array('utf8mb4_0900_bin', $available, true) ? 'utf8mb4_0900_bin'
                : (in_array('utf8mb4_nopad_bin', $available, true) ? 'utf8mb4_nopad_bin' : 'utf8mb4_bin');
            $this->pdo->exec('ALTER TABLE ' . $songTable . ' ENGINE=InnoDB, ROW_FORMAT=DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->pdo->exec('ALTER TABLE ' . $songTable . ' MODIFY name VARCHAR(300) COLLATE ' . $collation . ' NOT NULL, MODIFY singer VARCHAR(300) COLLATE ' . $collation . ' NOT NULL,
                MODIFY type TEXT NOT NULL, MODIFY note TEXT NOT NULL, MODIFY time BIGINT NOT NULL');
            $this->pdo->exec('ALTER TABLE ' . $othersTable . ' ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, MODIFY value TEXT NOT NULL');
            $indexes = $this->pdo->query('SHOW INDEX FROM ' . $songTable)->fetchAll();
            $identity = [];
            foreach ($indexes as $index) {
                if ((int) $index['Non_unique'] === 0 && $index['Sub_part'] === null) $identity[$index['Key_name']][(int) $index['Seq_in_index']] = $index['Column_name'];
            }
            $hasIdentity = false;
            foreach ($identity as $columns) { ksort($columns); if (array_values($columns) === ['name','singer']) $hasIdentity = true; }
            if (!$hasIdentity) {
                if (in_array('song_identity', array_column($indexes, 'Key_name'), true)) $this->pdo->exec('ALTER TABLE ' . $songTable . ' DROP INDEX song_identity');
                $this->pdo->exec('ALTER TABLE ' . $songTable . ' ADD UNIQUE KEY song_identity (name,singer)');
            }
            $this->pdo->beginTransaction();
            $this->insertRows($database, $temporary['song'], $songs);
            $this->insertRows($database, $temporary['others'], $snapshot['others']);
            $password = password_hash('123456', PASSWORD_DEFAULT);
            $statement = $this->pdo->prepare('INSERT INTO ' . $othersTable . ' (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?');
            $statement->execute(['passWd', $password, $password]);
            $this->pdo->commit();
            if (preg_match('/AUTO_INCREMENT=(\d+)/', $snapshot['song_schema'], $match)) $this->pdo->exec('ALTER TABLE ' . $songTable . ' AUTO_INCREMENT=' . (int) $match[1]);
            if ((int) $this->pdo->query('SELECT COUNT(*) FROM ' . $songTable)->fetchColumn() !== count($songs)) throw new RuntimeException('迁移后的歌曲数量不一致');
            $renames = [];
            foreach (['song', 'others'] as $suffix) {
                $renames[] = $this->table($database, $target[$suffix . '_table']) . ' TO ' . $this->table($database, $backups[$suffix]);
                $renames[] = $this->table($database, $temporary[$suffix]) . ' TO ' . $this->table($database, $target[$suffix . '_table']);
            }
            // 新表全部准备好后再切换，旧表保留为数据库内备份。
            $this->pdo->exec('RENAME TABLE ' . implode(', ', $renames));
            return $backups;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            foreach ($temporary as $table) {
                try { $this->pdo->exec('DROP TABLE IF EXISTS ' . $this->table($database, $table)); } catch (Throwable) {}
            }
            error_log('迁移失败：' . $error->getMessage());
            throw new RuntimeException('迁移未完成，原表保留。请检查数据库的建表、修改结构和重命名权限及字段内容。', 503, $error);
        }
    }

    /** 将原始字段写入新表，跳过生成列；入参：数据库、表名、记录；返回：无。 */
    private function insertRows(string $database, string $table, array $rows): void
    {
        if ($rows === []) return;
        $columns = [];
        foreach ($this->pdo->query('SHOW COLUMNS FROM ' . $this->table($database, $table))->fetchAll() as $column) {
            if (!str_contains((string) $column['Extra'], 'VIRTUAL GENERATED') && !str_contains((string) $column['Extra'], 'STORED GENERATED')) $columns[] = $column['Field'];
        }
        $statement = $this->pdo->prepare('INSERT INTO ' . $this->table($database, $table) . ' (' . implode(',', array_map($this->quote(...), $columns)) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')');
        foreach ($rows as $row) $statement->execute(array_map(static fn(string $column): mixed => $row[$column] ?? null, $columns));
    }

    /** 引用数据库与表名；入参：数据库、表名；返回：限定表名。 */
    private function table(string $database, string $table): string { return $this->quote($database) . '.' . $this->quote($table); }

    /** 转义数据库标识符；入参：标识符；返回：SQL 标识符。 */
    private function quote(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }
}
