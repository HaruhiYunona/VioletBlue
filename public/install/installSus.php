<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
if (!\VioletBlue\support\support_config::installed()) {
    header('Location: ./installer.php', true, 303);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="utf-8"><title>安装成功</title>
<link rel="stylesheet" href="../static/css/panel.min.css"></head>
<body class="min_body"><div class="suitable"><h1>安装成功</h1>
<p>请使用安装时设置的管理员密码<a href="../admin/index.php">登录管理后台</a>，并完善站点设置。</p>
<p>安装程序已锁定。请保留 install/.block 文件。</p>
<a href="../index.php">打开歌单</a></div></body></html>
