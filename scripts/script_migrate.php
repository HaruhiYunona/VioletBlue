<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

use VioletBlue\model\model_database;
use VioletBlue\support\support_config;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $pdo = model_database::connection();
    $song = model_database::table('song');
    $others = model_database::table('others');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . $song)->fetchColumn();
    $duplicates = $pdo->query('SELECT name,singer,COUNT(*) AS total FROM ' . $song . ' GROUP BY name,singer HAVING COUNT(*) > 1')->fetchAll();
    if ($duplicates !== []) {
        throw new RuntimeException('旧库存在重复歌名和歌手，请先处理重复记录后重新迁移：' . json_encode($duplicates, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    echo '当前数据库：' . $pdo->query('SELECT DATABASE()')->fetchColumn() . '；目标表：' . $song . '、' . $others . "。\n";
    echo '已找到歌曲 ' . $count . " 首。迁移保留歌曲、公告和标签；应用后将管理员密码重置为 123456。\n";
    if (!in_array('--apply', $argv, true)) {
        echo "预检查完成。备份旧库后执行 php scripts/script_migrate.php --apply 应用迁移。\n";
        exit(0);
    }
    $pdo->exec('ALTER TABLE ' . $others . ' ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, MODIFY value TEXT NOT NULL');
    $pdo->exec('ALTER TABLE ' . $song . ' ENGINE=InnoDB, ROW_FORMAT=DYNAMIC, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, MODIFY name VARCHAR(300) NOT NULL, MODIFY singer VARCHAR(300) NOT NULL, MODIFY time BIGINT NOT NULL');
    $indexes = $pdo->query('SHOW INDEX FROM ' . $song)->fetchAll();
    if (!in_array('song_identity', array_column($indexes, 'Key_name'), true)) {
        $pdo->exec('ALTER TABLE ' . $song . ' ADD UNIQUE KEY song_identity (name,singer)');
    }
    // 字段扩容和索引升级成功后重置当前站点账号；缺少密码记录时补建。
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $statement = $pdo->prepare('INSERT INTO ' . $others . ' (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value = ?');
    $statement->execute(['passWd', $password, $password]);
    support_config::atomic(support_config::publicRoot() . '/install/.block', 'migrated');
    echo "迁移完成。管理员密码已重置为 123456，请登录后在网站设置中修改。\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
