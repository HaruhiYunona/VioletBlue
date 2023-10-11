<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-批量增歌"; ?></title>
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
        <div class="title">批量新增歌曲</div>
        <div class="form-box">
            <div class="option-title">歌单列表<span style="font-size: 12px;">*直接从Excel复制到此框内</span></div>
            <textarea class="input-area" id="songList" wrap="physical" placeholder="e.g.:&#10;青花瓷 周杰伦 舰长,流行 星川真纪♥&#10;菊花台 周杰伦 舰长,流行,痛&#10;黄金甲 周杰伦 舰长,流行 珉昭♥"></textarea>
            <button class="mid-button" style="margin-top: 40px;">
                提交
            </button>
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
            document.getElementsByClassName('mid-button')[0].addEventListener('click', () => {
                submitSong(document.getElementById('songList').value);
            });
        }


        /**
         * 提交歌曲函数
         */
        const submitSong = (songList) => {
            if (load_flag == 0) {
                loadVisable('正在处理,请勿退出...');
                alertify.set('notifier', 'position', 'top-center');
                alertify.set('notifier', 'delay', 2);
                let list = songList.split("\n");
                let listLength = list.length;
                let songArr = [];
                for (let i = 0; listLength > i; i++) {
                    if (list[i]) {
                        let info = list[i].split("\t");
                        let name = info[0].trim();
                        let singer = (info[1] != undefined) ? strim(info[1], "\s，,") : '未知';
                        let claim = (info[3]) ? info[3] : "";
                        let tags = strim(info[2], ",，\s").match(/([^,，\s]+)/g);
                        songArr.push({
                            name: name,
                            singer: singer,
                            claim: claim,
                            tags: JSON.stringify(tags)
                        });
                    }
                }
                if (songArr.length >= 1) {
                    post('../api/submitSongs', {
                        passWd: $_COOKIE('passWd'),
                        list: JSON.stringify(songArr)
                    }).then((response) => {
                        loadHide();
                        if (response.code == 1) {
                            alertify.success(response.content);
                        } else {
                            alertify.error(response.content);
                        }
                    })
                } else {
                    loadHide();
                    alertify.error("请 检 查 您 是 否 有 选 项 未 填");
                }
            }
        }

        const strim = (str, char) => {
            if (char) {
                str = str.replace(new RegExp('^[\\' + char + ']+|[\\' + char + ']+$', 'g'), '');
            }
            return str.replace(/^\s+|\s+$/g, '');
        }
    </script>
</body>