<?php
declare(strict_types=1);

use VioletBlue\model\model_database;
use VioletBlue\service\service_migration;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

try {
    // 支持网站根目录或 scripts 目录，以及源码与安装包两种布局。
    $loaded = false;
    foreach ([__DIR__ . '/app/bootstrap.php', dirname(__DIR__) . '/app/bootstrap.php', __DIR__ . '/src/bootstrap.php', dirname(__DIR__) . '/src/bootstrap.php'] as $bootstrap) {
        if (is_file($bootstrap)) { require_once $bootstrap; $loaded = true; break; }
    }
    if (!$loaded) throw new RuntimeException('未找到程序入口。请将脚本放在网站根目录或 scripts 目录，并使用 PHP 8.4.1+ 执行。');
    $database = (string) model_database::connection()->query('SELECT DATABASE()')->fetchColumn();
    $prefix = substr(trim(model_database::table('song'), '`'), 0, -5);
    $service = new service_migration();
    $preview = $service->preview($database, $prefix);
    echo '当前数据库：' . $database . '；目标表：' . $preview['song_table'] . '、' . $preview['others_table'] . "。\n";
    echo '歌曲 ' . $preview['total'] . ' 首，重复组 ' . count($preview['groups']) . ' 组，处理后保留 ' . $preview['remaining'] . " 首。\n";
    foreach ($preview['groups'] as $group) echo json_encode($group, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    if (!in_array('--apply', $argv, true)) {
        echo "预览完成。执行本脚本并添加 --apply --deduplicate 去重迁移，或 --apply --merge 合并迁移。迁移后密码为 123456。\n";
        exit(0);
    }
    if ($preview['groups'] !== [] && !in_array('--merge', $argv, true) && !in_array('--deduplicate', $argv, true)) throw new RuntimeException('存在重复歌曲，请添加 --deduplicate 或 --merge 选择处理方式。');
    $result = $service->apply($database, $prefix, in_array('--merge', $argv, true) ? 'merge' : 'deduplicate', $preview['fingerprint']);
    echo '迁移完成，保留 ' . $result['total'] . ' 首歌曲。原表备份：' . implode('、', $result['backups']) . "。\n管理员密码已重置为 123456，请登录后修改。\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
