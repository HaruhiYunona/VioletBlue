<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80401) {
    http_response_code(500);
    exit('VioletBlue requires PHP 8.4.1 or later.');
}
require_once __DIR__ . '/support/support_update_guard.php';
\VioletBlue\support\support_update_guard::check(dirname(__DIR__));
require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Shanghai');

// 将未被屏蔽的 PHP 警告转换为异常，交给统一异常处理。
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $error->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['code' => 0, 'content' => '服务暂时不可用，请检查服务器日志及数据库配置', 'error' => \VioletBlue\support\support_api_error::ServerError->value], JSON_UNESCAPED_UNICODE);
});

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    \VioletBlue\service\service_auth::start();
}
