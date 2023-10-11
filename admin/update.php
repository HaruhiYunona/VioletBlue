<?php
$siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
}
?>





<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-获取更新"; ?></title>
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
            <span class="tool_exit"><img src="../static/img//arrow-left-circle.svg" class="tool_icon">退出</span>
        </div>
        <div class="title">检查更新</div>
        <div class="title_bar">
            <span class="ver_title" id="title"></span><span id="new_version" class="new_version" style="display: none;">新版本</span><span id="lastest_version" class="lastest_version" style="display: none;">已是最新版本</span>
        </div>
        <div class="change_box">
            <div class="version_info">
                <span id="version_tag">version</span>
                <p id="version"></p>
                <span id="res_tag">resource</span>
                <p id="res"></p>
                <span class="lastest_push">pushing date</span>
                <p id="pushing"></p>
            </div>
            <div class="change" id="change"></div>
        </div>
        <button class="get_update" style="display:none">获取更新</button>
        <div class="title">更新日志</div>
        <div style="margin-top: 20px;font-size:smaller;" id="log">
        </div>
    </div>
    <script>
        let currentVersion = '';
        window.onload = () => {
            document.getElementsByClassName('tool_bar')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });
            post('../api/getVersion', {}).then((response) => {
                currentVersion = response;
            });
            post('../api/getUpdate', {}).then((response) => {
                if (response.code == 1) {
                    document.getElementsByClassName('get_update')[0].addEventListener('click', () => {
                        window.location.href = response.lastest.link;
                    });
                    let length = response.log.length;
                    let htm = '';
                    let tags = {
                        push: ['lastest_push', '发行'],
                        fix: ['new_version', '修复'],
                        cof: ['lastest_version', '优化']
                    }
                    for (let i = 0; i < length; i++) {
                        htm = htm + '<span>' + response.log[i].time + '</span><span class="' + tags[response.log[i].main][0] + '">' +
                            tags[response.log[i].main][1] + '</span><p class="log_content">' + response.log[i].change + '</p>';
                    }
                    document.getElementById('log').innerHTML = htm;
                    useValue(['title', 'pushing', 'change'], [response.lastest.title, response.lastest.time, response.lastest.change]);
                    if (response.lastest.res <= currentVersion.res && response.lastest.version <= currentVersion.version) {
                        useValue(['version', 'res'], [currentVersion.version, currentVersion.res]);
                        document.getElementById('lastest_version').style = 'display:inline-block;';
                        document.getElementById('version_tag').setAttribute('class', 'lastest_version');
                        document.getElementById('res_tag').setAttribute('class', 'lastest_version');
                    } else {
                        document.getElementsByClassName('get_update')[0].style = "display:inline-block";
                        if (response.lastest.res > currentVersion.res && response.lastest.version > currentVersion.version) {
                            useValue(['version', 'res'], [currentVersion.version + ' → ' + response.lastest.version, currentVersion.res + ' → ' + response.lastest.res]);
                            document.getElementById('new_version').style = 'display:inline-block;';
                            document.getElementById('version_tag').setAttribute('class', 'new_version');
                            document.getElementById('res_tag').setAttribute('class', 'new_version');
                        } else if (response.lastest.version <= currentVersion.version) {
                            useValue(['version', 'res'], [currentVersion.version, currentVersion.res + ' → ' + response.lastest.res]);
                            document.getElementById('new_version').style = 'display:inline-block;';
                            document.getElementById('version_tag').setAttribute('class', 'lastest_version');
                            document.getElementById('res_tag').setAttribute('class', 'new_version');
                        } else {
                            useValue(['version', 'res'], [currentVersion.version + ' → ' + response.lastest.version, response.lastest.res]);
                            document.getElementById('new_version').style = 'display:inline-block;';
                            document.getElementById('version_tag').setAttribute('class', 'new_version');
                            document.getElementById('res_tag').setAttribute('class', 'lastest_version');
                        }
                    }
                }
            });
        }
    </script>
</body>