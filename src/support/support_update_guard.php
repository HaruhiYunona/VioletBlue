<?php
declare(strict_types=1);
namespace VioletBlue\support;

/** 更新替换程序期间返回短暂维护状态，进度查询可继续访问。 */
final class support_update_guard
{
    /** 用途：在加载程序之前拦截维护期请求；入参：私有程序根目录；返回值：无。 */
    public static function check(string $root): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $progress = $path === '/api.php' && ($_GET['action'] ?? null) === 'getUpdateStatus' || $path === '/env/api/v1/getUpdateStatus';
        if (PHP_SAPI === 'cli' || $progress) return;
        $path = $root . '/storage/updates/maintenance.json';
        if (!is_file($path)) return;
        $state = json_decode((string) file_get_contents($path), true);
        if (!is_array($state) || ($state['expires'] ?? 0) < time()) return;
        http_response_code(503);
        header('Retry-After: 5'); header('Cache-Control: no-store'); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => 0, 'content' => '网站正在更新，请稍后刷新', 'error' => 'SERVICE_UNAVAILABLE'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
