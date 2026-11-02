<?php
declare(strict_types=1);
// 仅用于 php -S；生产环境请使用 public 作为站点根目录。
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path)) {
    http_response_code(400);
    return true;
}
if (str_starts_with($path, '/env/api/v1/')) {
    require __DIR__ . '/public/api.php';
    return true;
}
$decoded = rawurldecode($path);
$file = realpath(__DIR__ . '/public' . $decoded);
$root = realpath(__DIR__ . '/public');
if (preg_match('~(?:^|/)\.~', $decoded) || ($file !== false && $root !== false && !str_starts_with($file, $root . DIRECTORY_SEPARATOR) && $file !== $root)) {
    http_response_code(404);
    return true;
}
if ($file !== false && (is_file($file) || (is_dir($file) && is_file($file . '/index.php')))) {
    return false;
}
if ($path === '/') {
    require __DIR__ . '/public/index.php';
    return true;
}
http_response_code(404);
echo 'Not Found';
return true;
