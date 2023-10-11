<?php

function is_installer()
{
    return !file_exists('./.block');
}


if (is_installer()) {
    header("Location:../index.php");
    return 0;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>安装VioletBlue成功</title>
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

        a {
            color: brown;
        }
    </style>
    <div class="suitable">
        <p class="title" style="margin-top: 30px;width:100%">安装成功</p>
        <img src="./icon.avif" class="installer_icon">
        <p>恭喜您安装成功了!现在您已经可以访问您的站点。不过在此之前请您先设置好您的站点。<br>
            点击这里开始设置→<a href="../admin/setting.php" target="_blank">../admin/setting.php</a>
        </p><br><br>
        <p>
            您的默认密码是:123456<br><br>
            管理页面是:<a href="<?php $url  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $url .= "://" . $_SERVER['HTTP_HOST'];
                            $url .= "/admin";
                            echo $url; ?>" target="_blank"><?php $url  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                                            $url .= "://" . $_SERVER['HTTP_HOST'];
                                                            $url .= "/admin";
                                                            echo $url;unlink('./installSus.php') ?></a>
            <br>
            请务必记住管理后台的网址为：你的域名+"/admin"
            <br>
            <br>
            安装残余文件已被清理。
        </p>
    </div>
</body>