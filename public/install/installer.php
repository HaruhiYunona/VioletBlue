<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
if (\VioletBlue\support\support_config::installed()) {
    header('Location: ../index.php', true, 303);
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>安装VioletBlue</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../static/css/panel.min.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />

</head>


<body class="min_body">
    <style>
        .installer_icon {
            width: 240px;
            height: 89px;
            margin-left: calc(50% - 120px);
            margin-top: 50px;
        }

        input {
            text-transform: none;
        }
    </style>
    <div class="suitable">
        <p class="title" style="margin-top: 30px;width:100%">安装VioletBlue</p>
        <img src="./icon.webp" class="installer_icon">

        <div class="form-box">
            <form action="./installRun.php" method="post">
<input type="hidden" name="csrf" value="<?php echo \VioletBlue\support\support_input::escape($_SESSION['csrf']); ?>">
                <div class="option-title">数据库地址</div>
                <input class="option-input" name="db" value="localhost">
                <div class="option-intro">请填写您的数据库地址,若为服务器本地数据库,请填写localhost。</div>
                <div class="option-title">数据库名称</div>
                <input class="option-input" name="dbname" value="VioletBlue">
                <div class="option-intro">请填写您的数据库名称。</div>
                <div class="option-title">数据库登录用户名</div>
                <input class="option-input" name="dbuser" value="root">
                <div class="option-intro">请填写您的数据库用户名。</div>
                <div class="option-title">数据库登录密码</div>
                <input class="option-input" name="dbpassWd" type="password" value="">
                <div class="option-intro">请填写您的数据库密码。</div>
                <div class="option-title">数据库端口</div>
                <input class="option-input" name="dbport" value="3306">
                <div class="option-intro">请填写您的数据库端口。如果没有调整过,mysql默认端口应为3306。</div>
                <div class="option-title">表名前缀</div>
                <input class="option-input" name="tablepre" value="VB">
                <div class="option-intro">该设置不重要,你可以根据喜好填写字母和数字,也可以直接使用默认值“VB”</div>
                <div class="option-title">管理员密码</div><input class="option-input" name="admin_password" type="password" minlength="6" maxlength="72" required autocomplete="new-password"><div class="option-intro">请设置至少6字的管理员密码。安装不会覆盖已有数据库表。</div>
                <input type="submit" class="get_update" style="margin-top:50px;margin-bottom:100px;" value="开始安装">
            </form>
        </div>
</body>
