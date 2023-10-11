<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-编辑歌曲"; ?></title>
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
        <div class="title">修改歌曲</div>
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
            <div class="button-box">
                <button class="mid-button" id="delet">
                    <div class="inner-cutline inner-btn"></div>删除
                </button>
                <button class="mid-button" id="submit" style="margin-top: 15px;">
                    <div class="inner-cutline inner-btn"></div>修改
                </button>
            </div>
        </div>
    </div>
    <footer style="position:absolute;bottom:50px;width:100%;text-align:center">
        <a href="https://github.com/HaruhiYunona/VioletBlue/" style="color:#000;font-size:small;">Github@HaruhiYunona<br>Obj#VioletBlue -MIT License</a>
    </footer>
    <script>
        window.onload = function() {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            script();
            document.getElementsByClassName('tool_bar')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });
            document.getElementById('submit').addEventListener('click', () => {
                let tags = document.getElementsByClassName('tag-selected');
                let tagLength = tags.length;
                let tagString = [];
                for (let i = 0; tagLength > i; i++) {
                    if (tags[i].innerHTML != undefined && tags[i].innerHTML != null) {
                        tagString.push(tags[i].innerHTML);
                    }
                }
                let tagList = JSON.stringify(tagString);
                editSong(document.getElementById('name').value, document.getElementById('singer').value, document.getElementById('claim').value, tagList);
            });
            document.getElementById('delet').addEventListener('click', () => {
                alertify.confirm("删除歌曲", "确定删除这首歌吗?",
                    function() {
                        deletSong($_GET('uid'));
                    },
                    function() {
                        alertify.set('notifier', 'position', 'top-center');
                        alertify.set('notifier', 'delay', 2);
                        alertify.error('已取消');
                    });
            });
        }


        const script = () => {
            //获取歌单类型
            post('../api/getTagList').then((response) => {
                if (response.code == 1) {
                    let htmType = '';
                    let typeLength = response.content.length;
                    for (let i = 1; typeLength > i; i++) {
                        htmType = htmType + '<li class="tag" id="' + response.content[i] + '">' + response.content[i] + '</li>';
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
            //获取歌曲信息
            let uid = $_GET('uid');
            post('../api/detail', {
                uid: uid
            }, true).then((response) => {
                if (response.code == 1) {
                    document.getElementById('name').value = response.content.name;
                    document.getElementById('singer').value = response.content.singer;
                    document.getElementById('claim').value = response.content.claim;
                    let tagsLength = response.content.tags.length;
                    for (let i = 0; tagsLength > i; i++) {
                        document.getElementById(response.content.tags[i]).setAttribute('class', 'tag-selected')
                    }
                } else {
                    window.location.replace('../index.html');
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
         * 编辑歌曲函数
         */
        const editSong = (name, singer, claim, tags) => {
            if (load_flag == 0) {
                loadVisable('修改中...');
                if (name.length > 0 && singer.length > 0 && tags.length > 2) {
                    post('../api/edit', {
                        passWd: $_COOKIE('passWd'),
                        uid: $_GET('uid'),
                        name: name,
                        singer: singer,
                        claim: claim,
                        tags: tags
                    }, true).then((response) => {
                        loadHide();
                        if (response.code == 1) {
                            alertify.success("修 改 信 息 成 功");
                        } else {
                            alertify.error("修 改 失 败");
                        }
                    })
                } else {
                    loadHide();
                    alertify.error("请 检 查 您 是 否 有 选 项 未 填");
                }
            }
        }

        /**
         * 删除歌曲函数
         */
        const deletSong = (uid) => {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            if (uid !== null && uid !== undefined) {
                post('../api/delete', {
                    uid: $_GET('uid'),
                    passWd: $_COOKIE('passWd')
                }, true).then((response) => {
                    if (response.code == 1) {
                        alertify.alert("删除歌曲", "本歌曲删除成功,点击确定回到列表。", function() {
                            window.location.replace('../index.php');
                        });
                    } else {
                        alertify.error("删 除 失 败");
                    }
                })
            } else {
                alertify.error("未 能 找 到 歌 曲");
            }
        }
    </script>
</body>