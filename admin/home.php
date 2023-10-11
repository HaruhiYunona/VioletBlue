<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-管理后台"; ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../static/css/panel.min.css" />
    <script src="../static/js/script.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" href="../static/css/alertify.min.css" />
    <script src="../static/js/alertify.min.js"></script>

</head>

<body class="min_body">
    <div class="suitable">
        <div class="title">管理功能列表</div>
        <ul class="admin_ul">
            <li class="admin_card" data="./add.php">
                <p class="admin_func"><span><img src="../static/img/music.svg" class="admin_icon"></span>新增歌曲</p>
                <p class="admin_desc">单独新增一首歌曲。UI更加直观简洁。</p>
            </li>
            <li class="admin_card" data="./quick.php">
                <p class="admin_func"><span><img src="../static/img/layers.svg" class="admin_icon"></span>批量新增</p>
                <p class="admin_desc">从excel表格文件直接复制内容到输入框,并批量增加歌曲。</p>
            </li>
            <li class="admin_card" data="../index.php">
                <p class="admin_func"><span><img src="../static/img/edit-3.svg" class="admin_icon"></span>修改歌曲</p>
                <p class="admin_desc">打开列表,点击歌曲后修改歌曲信息。</p>
            </li>
            <li class="admin_card" data="./option.php">
                <p class="admin_func"><span><img src="../static/img/toggle-left.svg" class="admin_icon"></span>网站设置</p>
                <p class="admin_desc">修改网站功能设置。</p>
            </li>
            <li class="admin_card" data="./update.php">
                <p class="admin_func"><span><img src="../static/img/download-cloud.svg" class="admin_icon"></span>日志与更新</p>
                <p class="admin_desc">获取最新版本网站代码或者查看更新日志。</p>
            </li>
            <li class="admin_card" data="./setting.php">
                <p class="admin_func"><span><img src="../static/img/settings.svg" class="admin_icon"></span>基础设置</p>
                <p class="admin_desc">修改网站基本设置。</p>
            </li>
            <li class="admin_card" data="https://github.com/HaruhiYunona/VioletBlue/">
                <p class="admin_func"><span><img src="../static/img/archive.svg" class="admin_icon"></span>代码仓库</p>
                <p class="admin_desc">访问作者项目仓库,获取最新信息。</p>
            </li>
            <li class="admin_card" data="./contact.php">
                <p class="admin_func"><span><img src="../static/img/smartphone.svg" class="admin_icon"></span>联系方式</p>
                <p class="admin_desc">联系作者。</p>
            </li>
            <li class="admin_card" data="./agreement.php">
                <p class="admin_func"><span><img src="../static/img/git-pull-request.svg" class="admin_icon"></span>利用协议</p>
                <p class="admin_desc">请仔细确认协议。该项目代码80%以上来自作者原创编写,合法使用避免侵权。</p>
            </li>
        </ul>
    </div>
    <footer style="position:absolute;bottom:50px;width:100%;text-align:center">
        <a href="https://github.com/HaruhiYunona/VioletBlue/" style="color:#000;font-size:small;">Github@HaruhiYunona<br>Obj#VioletBlue -MIT License</a>
    </footer>
    <script>
        window.onload = function() {
            let func_card = document.getElementsByClassName('admin_card');
            for (let i = 0; i < func_card.length; i++)
                func_card[i].addEventListener('click', () => {
                    open(func_card[i].getAttribute('data'), '_blank');
                });
        }
    </script>
</body>