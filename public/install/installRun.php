<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

use VioletBlue\support\support_config;
use VioletBlue\service\{service_auth, service_installer};

if (support_config::installed()) {
    header('Location: ../index.php', true, 303);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('请从安装页面提交表单');
}
if (!service_auth::csrfValid()) {
    http_response_code(403);
    exit('CSRF 校验失败，请返回安装页面重试');
}
$installer = new service_installer();
try {
    $installer->install($_POST);
    header('Location: ./installSus.php', true, 303);
    exit;
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo \VioletBlue\support\support_input::escape($error->getMessage());
} catch (PDOException $error) {
    error_log((string) $error);
    http_response_code(422);
    echo \VioletBlue\support\support_input::escape($installer->databaseError($error));
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    echo '安装失败，请检查数据库连接、扩展、目录权限及服务器日志。已有数据不会被删除。';
}
