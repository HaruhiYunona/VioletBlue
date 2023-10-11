<?php

function is_installer()
{
    return file_exists('../install/.block');
}


if (is_installer()) {
    header("Location:../index.php");
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>安装VioletBlue</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../static/css/panel.min.css" />
    <script src="../static/js/script.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" href="../static/css/alertify.min.css" />
    <script src="../static/js/alertify.min.js"></script>

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
                <input class="option-input" name="dbpassWd" value="123456">
                <div class="option-intro">请填写您的数据库密码。</div>
                <div class="option-title">数据库端口</div>
                <input class="option-input" name="dbport" value="3306">
                <div class="option-intro">请填写您的数据库端口。如果没有调整过,mysql默认端口应为3306。</div>
                <div class="option-title">表名前缀</div>
                <input class="option-input" name="tablepre" value="VB">
                <div class="option-intro">该设置不重要,你可以根据喜好填写字母和数字,也可以直接使用默认值“VB”</div>
                <p class="option-intro">*重要提示:本安装程序将在安装成功后发送一段包含:随机生成的ID、服务器IP、服务器OS、服务器PHP版本、软件版本的信息用于改进软件。若您感到收集IP冒犯到您的隐私,请您将installRun.php中的<br><br>
                $server_ip = $_SERVER['SERVER_ADDR'];<br><br>改写为<br><br>$server_ip = '0.0.0.0';<br><br>再进行安装。</p>
                <input type="submit" class="get_update" style="margin-top:50px;margin-bottom:100px;" value="开始安装">
            </form>
        </div>
</body>