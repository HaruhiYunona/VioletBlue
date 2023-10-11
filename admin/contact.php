<?php
$siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
}
?>





<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-联系作者"; ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../static/css/panel.min.css" />
    <script src="../static/js/script.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" href="../static/css/alertify.min.css" />
    <script src="../static/js/alertify.min.js"></script>
</head>


<body class="min_body">
    <div class="suitable">
        <div class="tool_bar">
            <span class="tool_exit"><img src="../static/img/arrow-left-circle.svg" class="tool_icon">退出</span>
        </div>
        <div class="title">联系作者</div>
        <div class="contact">
            <p><img src="../static/img/mail.svg"><a href="mailto:2507317007@qq.com" target="_blank">2507317007@qq.com(推荐)</p>
            <p><img src="../static/img/message-circle.svg"><a href="" target="_blank">VOCALOID_FAN(推荐)</p>
            <p><img src="../static/img/mail.svg"><a href="mailto:lashanda13fg@gmail.com" target="_blank">lashanda13fg@gmail.com</p>
            <p><img src="../static/img/github.svg"><a href="https://github.com/HaruhiYunona" target="_blank">HaruhiYunona</p>
            <p><img src="../static/img/tv.svg"><a href="https://space.bilibili.com/104092258" target="_blank">星川真纪</p>
            <p><img src="../static/img/twitter.svg"><a href="https://x.com/Umi_Kuyo" target="_blank">@Umi_Kuyo</p>
        </div>
    </div>
    <footer style="position:absolute;bottom:50px;width:100%;text-align:center">
        <a href="https://github.com/HaruhiYunona/VioletBlue/" style="color:#000;font-size:small;">Github@HaruhiYunona<br>Obj#VioletBlue -MIT License</a>
    </footer>
    <script>
        window.onload = function() {
            document.getElementsByClassName('tool_bar')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });
        }
    </script>
</body>