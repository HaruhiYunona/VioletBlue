<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-添加歌曲"; ?></title>
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
        <div class="title">新增歌曲</div>
        <div class="form-box">
            <div class="option-title">歌名</div>
            <input class="option-input" id="name">
            <div class="option-intro">必填,歌曲名称。</div>
            <div class="option-title">歌手</div>
            <input class="option-input" id="singer">
            <div class="option-intro">必填,歌手名称,不知道歌手可以填未知。</div>
            <div class="option-title">舰长冠名</div>
            <input class="option-input" id="claim">
            <div class="option-intro">酌情填写。填写后,该歌曲将被高亮展示,并显示冠名人的昵称</div>
            <div class="option-title">标签<span style="font-size: 12px;">*点击变亮后即为选中。</span></div>
            <ul class="tag-area">
            </ul>
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
            script();
            document.getElementsByClassName('tool_bar')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });
            document.getElementsByClassName('mid-button')[0].addEventListener('click', () => {
                let tags = document.getElementsByClassName('tag-selected');
                let tagLength = tags.length;
                let tagString = [];
                for (let i = 0; tagLength > i; i++) {
                    if (tags[i].innerHTML != undefined && tags[i].innerHTML != null) {
                        tagString.push(tags[i].innerHTML);
                    }
                }
                let tagList = JSON.stringify(tagString);
                submitSong($_COOKIE('passWd'), document.getElementById('name').value, document.getElementById('singer').value, document.getElementById('claim').value, tagList);
            });
        }


        const script = () => {
            //获取歌单类型
            post('../api/getTagList').then((response) => {
                if (response.code == 1) {
                    let htmType = '';
                    let typeLength = response.content.length;
                    for (let i = 1; typeLength > i; i++) {
                        htmType = htmType + '<li class="tag">' + response.content[i] + '</li>';
                    }
                    document.getElementsByClassName('tag-area')[0].innerHTML = htmType;
                    let tag = document.getElementsByClassName('tag-area')[0].childNodes;

                    let tagLength = tag.length
                    for (let i = 0; tagLength > i; i++) {
                        tag[i].index = i;
                        tag[i].addEventListener('click', function() {
                            adminTagClick(this.index);
                        });
                    }
                }
            });

        }

        /**
         * 点击标签的事件
         */
        const adminTagClick = (index) => {
            let tags = document.getElementsByClassName('tag-area')[0].childNodes;
            let tag = tags[index];
            if (tag.getAttribute('class') == 'tag-selected') {
                tag.setAttribute('class', 'tag');
            } else {
                tag.setAttribute('class', 'tag-selected');
            }

        }

        /**
         * 提交歌曲事件
         */
        const submitSong = (passWd, name, singer, claim, tags) => {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            if (load_flag == 0) {
                loadVisable('执行中，请勿退出...');
                if (name.length > 0 && singer.length > 0 && tags.length > 2) {
                    post('../api/submitSong', {
                        passWd: $_COOKIE('passWd'),
                        name: name,
                        singer: singer,
                        claim: claim,
                        tags: tags
                    }, true).then((response) => {
                        loadHide();
                        if (response.code == 1) {
                            alertify.success("添 加 歌 曲 成 功");
                        } else if (response.code == 0) {
                            alertify.error(response.content)
                        } else {
                            alertify.error("未 知 原 因, 添 加 失 败");
                        }
                    })
                } else {
                    loadHide();
                    alertify.error("请 检 查 您 是 否 有 选 项 未 填");
                }
            }
        }
    </script>
</body>