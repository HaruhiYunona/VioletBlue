<?php
declare(strict_types=1);
namespace VioletBlue\view;

/**
 * 输出浏览器检测的共享模板。
 * @param string $section head 检测脚本与样式，body 提示内容
 * @return void
 */
function render_browser_guard(string $section): void
{
    if (!in_array($section, ['head', 'body'], true)) {
        throw new \InvalidArgumentException('无效的浏览器检测模板');
    }
    readfile(__DIR__ . '/template_browser_guard_' . $section . '.html');
}
