<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
} ?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-功能设置"; ?></title>
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
        <div class="title">功能设置</div>
        <div class="form-box">
            <div class="option-title">管理密码</div>
            <input class="option-input" id="passWd" value="123456">
            <div class="option-intro">网站管理后台密码。这个必须改,否则会被重制为默认密码123456</div>
            <div class="option-title">网站公告</div>
            <textarea class="input-area" id="notice" wrap="physical" style="min-height: 100px; width: calc(94% - 40px);;margin-left:3%"></textarea>
            <div class="option-intro">网站公告,将在个人信息下展示。</div>
            <div class="option-title">歌曲分类<span style="font-size: 12px;"> *输入标签并点确定添加.点击标签删除</span></div>
            <div>
                <input class="option-input tag_input" type="text" name="query" placeholder="" id="tag" mark="pc">
                <button class="tag_button">确定</button>
            </div>
            <ul class="tag-area">
            </ul>
        </div>
    </div>
    <footer style="position:absolute;bottom:50px;width:100%;text-align:center">
        <a href="https://github.com/HaruhiYunona/VioletBlue/" style="color:#000;font-size:small;">Github@HaruhiYunona<br>Obj#VioletBlue -MIT License</a>
    </footer>
    <script>
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

            document.getElementsByClassName('tag_button')[0].addEventListener('click', () => {
                addTag();
            });
        }

        const script = () => {
            //获取公告
            post('../api/getNotice').then((response) => {
                if (response.code == 1) {
                    document.getElementById('notice').value = response.content.replace(/\s*<\s*br\s*>\s*/g, '\n');
                }
            });
            //获取歌单类型
            post('../api/getTagList').then((response) => {
                if (response.code == 1) {
                    let htmType = '';
                    let typeLength = response.content.length;
                    for (let i = 1; typeLength > i; i++) {
                        htmType = htmType + '<li class="tag-selected">' + response.content[i] + '</li>';
                    }
                    document.getElementsByClassName('tag-area')[0].innerHTML = htmType;
                    let tag = document.getElementsByClassName('tag-area')[0].childNodes;

                    let tagLength = tag.length
                    for (let i = 0; tagLength > i; i++) {
                        tag[i].index = i;
                        tag[i].addEventListener('click', function() {
                            delTagClick(this.index);
                        });
                    }
                }
            });
        }

        const delTagClick = (index) => {
            let tags = document.getElementsByClassName('tag-area')[0].childNodes;
            let tag_del = tags[index];
            alertify.confirm("删除标签", "确定删除这个标签吗?",
                function() {
                    let tagsLength = tags.length;
                    for (let i = 0; tagsLength > i; i++) {
                        tags[i].index = i;
                        tags[i].removeEventListener('click', function() {
                            delTagClick(this.index);
                        });
                    }
                    tag_del.remove();
                    let tag = document.getElementsByClassName('tag-area')[0].childNodes;
                    let tagLength = tags.length;
                    for (let i = 0; tagLength > i; i++) {
                        tag[i].index = i;
                        tag[i].addEventListener('click', function() {
                            delTagClick(this.index);
                        });
                    }
                    alertify.set('notifier', 'position', 'top-center');
                    alertify.set('notifier', 'delay', 2);
                    alertify.success('已删除该标签');
                },
                function() {
                    alertify.set('notifier', 'position', 'top-center');
                    alertify.set('notifier', 'delay', 2);
                    alertify.error('已取消');
                });
        }

        const addTag = () => {
            let tag = document.getElementById('tag').value;
            if (tag != undefined && tag != '') {
                let index = document.getElementsByClassName('tag-area')[0].childNodes.length;
                let li = document.createElement('li');
                li.id = 'tag_id@new:' + tag;
                li.innerHTML = tag;
                document.getElementsByClassName('tag-area')[0].append(li);
                document.getElementById('tag_id@new:' + tag).setAttribute('class', "tag-selected");
                document.getElementById('tag_id@new:' + tag).index = index;
                document.getElementById('tag').value = '';
                document.getElementById('tag_id@new:' + tag).addEventListener('click', function() {
                    delTagClick(this.index);
                });
            }
        }

        const save = () => {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            if (load_flag == 0) {
                loadVisable('修改中,请勿刷新或退出...');
                let tags = document.getElementsByClassName('tag-selected');
                let tagLength = tags.length;
                let tagString = [];
                for (let i = 0; tagLength > i; i++) {
                    if (tags[i].innerHTML != undefined && tags[i].innerHTML != null) {
                        tagString.push(tags[i].innerHTML);
                    }
                }
                let tagList = JSON.stringify(tagString);
                config = {
                    notice: document.getElementById('notice').value,
                    passWd: document.getElementById('passWd').value,
                    tags: tagList
                };
                post('../api/changeSetting', {
                    passWd: $_COOKIE('passWd'),
                    config: JSON.stringify(config)
                }).then((response) => {
                    loadHide();
                    if (response.code == 1) {
                        setCookie("passWd","",-1);
                        alertify.success("修改 成 功");
                        setTimeout(() => {
                            window.opener = null;
                            window.open('', '_self');
                            window.close();
                        }, 1000);
                    } else {
                        alertify.error("修改 失 败");
                    }
                });
            }
        }
    </script>
</body>