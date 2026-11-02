<?php
declare(strict_types=1);
namespace VioletBlue\view;

use RuntimeException;
use VioletBlue\service\{service_auth, service_options, service_theme};
use VioletBlue\support\{support_config, support_input};
require_once __DIR__ . '/view_browser_guard.php';

/**
 * 读取 Vite manifest 和站点配置，输出首屏加载外壳。
 * @return void 输出 HTML 页面
 */
function render_app(): void
{
    $manifestPath = support_config::publicRoot() . '/build/.vite/manifest.json';
    if (!is_file($manifestPath)) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>前端尚未构建</title><p>请先在 frontend 目录运行 yarn install 和 yarn build。</p></html>';
        return;
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
    $entry = $manifest['index.html'] ?? null;
    if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
        throw new RuntimeException('前端构建 manifest 无效');
    }
    $site = (new service_options())->info();
    $theme = (new service_theme())->info();
    $boot = ['site' => $site, 'theme' => $theme, 'authenticated' => service_auth::loggedIn()];
    session_write_close();
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $escape = support_input::escape(...);
    $styles = [];
    $preloads = [];
    $visited = [];
    // 只遍历入口的静态依赖；后台路由分片保持按需加载。
    $visit = static function (string $key) use (&$visit, &$visited, &$styles, &$preloads, $manifest): void {
        if (isset($visited[$key])) {
            return;
        }
        $visited[$key] = true;
        $chunk = $manifest[$key] ?? null;
        if (!is_array($chunk)) {
            return;
        }
        foreach ($chunk['css'] ?? [] as $css) {
            $styles[$css] = true;
        }
        foreach ($chunk['imports'] ?? [] as $import) {
            $imported = $manifest[$import]['file'] ?? null;
            if (is_string($imported)) {
                $preloads[$imported] = true;
            }
            $visit($import);
        }
    };
    $visit('index.html');
    ?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="<?= $escape((string) $site['detail']) ?>">
    <title><?= $escape((string) $site['title']) ?></title>
    <link rel="icon" href="/favicon.ico">
    <?php render_browser_guard('head'); ?>
    <style>
    /* 首屏加载样式内联，外部 CSS 尚未返回时也能显示。 */
    body[data-booting]{margin:0;background:#fff}body[data-booting] #app{visibility:hidden}.boot-screen{position:fixed;inset:0;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;background:#fff;color:#9370db;font:13px system-ui;transition:opacity .2s}.boot-orbit{width:34px;height:34px;border:3px solid #eee8fa;border-top-color:#9370db;border-radius:50%;animation:boot-spin .8s linear infinite}.boot-screen.is-complete{opacity:0;pointer-events:none}.boot-screen button{display:none;border:0;border-radius:7px;background:#9370db;color:#fff;padding:8px 16px;cursor:pointer}.boot-screen.boot-error button{display:block}.boot-error .boot-orbit{animation:none}@keyframes boot-spin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.boot-orbit{animation:none}}
    </style>
    <?php foreach (array_keys($styles) as $file): ?><link class="boot-style" rel="preload" as="style" href="/build/<?= $escape($file) ?>" onload="this.onload=null;this.rel='stylesheet'"><noscript><link rel="stylesheet" href="/build/<?= $escape($file) ?>"></noscript><?php endforeach; ?>
    <?php foreach (array_keys($preloads) as $file): ?><link rel="modulepreload" href="/build/<?= $escape($file) ?>"><?php endforeach; ?>
</head>
<body data-booting>
    <?php render_browser_guard('body'); ?>
    <div id="boot-loader" class="boot-screen" role="status" aria-live="polite"><span class="boot-orbit" aria-hidden="true"></span><p>正在加载…</p><button type="button" onclick="location.reload()">重新加载</button></div>
    <div id="app"></div>
    <noscript><style>#boot-loader{display:none}</style>此歌单需要启用 JavaScript。</noscript>
    <script>window.__VB_BOOTSTRAP__ = <?= json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;</script>
    <script type="module" src="/build/<?= $escape($entry['file']) ?>"></script>
</body>
</html>
<?php
}
