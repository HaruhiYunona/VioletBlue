<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-网站设置"; ?></title>
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
            <span class="tool_save"><img src="../static/img/check-circle.svg" class="tool_icon">保存</span>
        </div>
        <div class="title">网站设置</div>
        <div class="form-box">
            <div class="option-title">网站名称</div>
            <input class="option-input" id="title">
            <div class="option-intro">必填,网站名称,将显示在浏览器顶部栏。</div>
            <div class="option-title">网站描述</div>
            <input class="option-input" id="description">
            <div class="option-intro">非必须,网站描述,将在搜索引擎显示为介绍。</div>
            <div class="option-title">Vup Mid</div>
            <input class="option-input" id="mid">
            <div class="option-intro">选填,VUP的Mid。在B站空间可以看到。如果是Vtuber请填0,并将下方手动信息补齐。</div>
            <div class="option-title">Vtuber个人档案</div>
            <input class="option-input" id="nickname">
            <div class="option-intro">选填,VTuber昵称。已填写VUP mid就无需填写这个。</div>
            <input class="option-input" id="level">
            <div class="option-intro">选填,VTuber等级。已填写VUP mid就无需填写这个。</div>
            <input class="option-input" id="following">
            <div class="option-intro">选填,VTuber关注。已填写VUP mid就无需填写这个。</div>
            <input class="option-input" id="follower">
            <div class="option-intro">选填,VTuber粉丝。已填写VUP mid就无需填写这个。</div>
            <input class="option-input" id="sign">
            <div class="option-intro">选填,VTuber自我介绍。已填写VUP mid就无需填写这个。</div>
            <div class="option-title">ICP备案号</div>
            <input class="option-input" id="icp">
            <div class="option-intro">非必须,网站ICP备案码,将在底部显示。注意,这是中国网站规范。</div>
            <div class="option-title">网安备案号</div>
            <input class="option-input" id="net">
            <div class="option-intro">非必须,网站网安备案码,将在底部显示。注意,这是中国网站规范。</div>
            <div class="option-title">API缓存时间</div>
            <input class="option-input" id="api_cache_time">
            <div class="option-intro">必须,API缓存时间。如果不常更新歌单,越长越好.单位是秒。</div>
            <button class="option-btn" id="face_img" onclick="javascript:jumpToUpload('face');">更改头像</button>
            <div class="option-intro">点击上传,VTuber头像。已填写VUP mid就无需动这个。否则请上传您的头像,推荐大小256*256,200kb以下。</div>
            <button class="option-btn" id="ico_img" onclick="javascript:jumpToUpload('favicon');">更改网站图标</button>
            <div class="option-intro">点击上传。网站图标。将于浏览器窗口栏显示,标准格式是ico,推荐大小64*64px,24kb以下。</div>
            <button class="option-btn" id="background_img" onclick="javascript:jumpToUpload('background');">更改背景</button>
            <div class="option-intro">点击上传。修改网站背景图片。推荐大小1920*1080,2MB以下。</div>
            <button class="option-btn" id="font" onclick="javascript:jumpToUpload('ifont');">修改字体</button>
            <div class="option-intro">点击上传。修改网站个人信息字体。推荐ttf格式并取子集。</div>
        </div>
    </div>
    <script>
        let orgin = '';
        window.onload = function() {
            script();
            document.getElementsByClassName('tool_exit')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });

            document.getElementsByClassName('tool_save')[0].addEventListener('click', () => {
                save();
            });
        }

        const jumpToUpload = (type) => {
            window.open('./upload.php?type=' + type, '_blank');
        }

        const script = () => {
            post('../api/siteInfo', {}).then((response) => {
                orgin = response.content;
                useVar(['title', 'description', 'mid', 'nickname', 'level', 'following', 'follower', 'sign', 'icp', 'net', 'api_cache_time'], [response.content.title, response.content.detail, response.content.mid, response.content.nickname, response.content.level, response.content.following, response.content.follower, response.content.sign, response.content.icp, response.content.net, response.content.api_cache_time])
            });
        }

        const save = () => {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            if (load_flag == 0) {
                loadVisable('修改中,请勿刷新或退出...');
                let config = [];
                config.push({
                    name: 'SITE_TITLE',
                    orgin: orgin.title,
                    new: document.getElementById('title').value
                }, {
                    name: 'SITE_DETAIL',
                    orgin: orgin.detail,
                    new: document.getElementById('description').value
                }, {
                    name: 'VUP_MID',
                    orgin: orgin.mid,
                    new: document.getElementById('mid').value
                }, {
                    name: 'VTB_NICKNAME',
                    orgin: orgin.nickname,
                    new: document.getElementById('nickname').value
                }, {
                    name: 'VTB_LEVEL',
                    orgin: orgin.level,
                    new: document.getElementById('level').value
                }, {
                    name: 'VTB_FOLLOWING',
                    orgin: orgin.following,
                    new: document.getElementById('following').value
                }, {
                    name: 'VTB_FOLLOWER',
                    orgin: orgin.follower,
                    new: document.getElementById('follower').value
                }, {
                    name: 'VTB_SIGN',
                    orgin: orgin.sign,
                    new: document.getElementById('sign').value
                }, {
                    name: 'API_CACHE_TIME',
                    orgin: orgin.api_cache_time,
                    new: document.getElementById('api_cache_time').value
                }, {
                    name: 'ICP_FILINGS',
                    orgin: orgin.icp,
                    new: document.getElementById('icp').value
                }, {
                    name: 'NETWORK_FILINGS',
                    orgin: orgin.net,
                    new: document.getElementById('net').value
                });

                post('../api/changeOption', {
                    passWd: $_COOKIE('passWd'),
                    config: JSON.stringify(config)
                }).then((response) => {
                    loadHide();
                    if (response.code == 1) {
                        alertify.success("修 改 成 功");
                        setTimeout(() => {
                            window.opener = null;
                            window.open('', '_self');
                            window.close();
                        }, 1000);
                    } else {
                        alertify.error("修 改 失 败");
                    }
                });
            }
        }
    </script>
</body>