<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$action = $path === '/api.php' ? ($_GET['action'] ?? null) : null;
if (is_string($path) && preg_match('~\A/env/api/v1/([A-Za-z]+)\z~D', $path, $match)) {
    $action = $match[1];
}
if (!is_string($action) || !preg_match('/\A[A-Za-z]+\z/D', $action)) {
    \VioletBlue\router\router_api::respond('接口不存在', 0, 404);
    return;
}
\VioletBlue\router\router_api::run($action);
