<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (empty($_COOKIE['passWd'])) {
    header('Location: ./index.php');
}
$type =  ($_GET['type'] != null) ? $_GET['type'] : 'face';
?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-上传文件"; ?></title>
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
        <div class="title">上传文件</div>
        <div class="content">

            <div class="box">
                <input type="file" name="file-1[]" id="file-1" class="inputfile inputfile-1" data-multiple-caption="{count} files selected" multiple="false" />
                <label for="file-1"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17">
                        <path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z" />
                    </svg> <span>点击选择文件</span></label>
            </div>
        </div>
        <button class="mid-button" style="margin-top: 40px;">
            提交
        </button>
    </div>
    <footer style="position:absolute;bottom:50px;width:100%;text-align:center">
        <a href="https://github.com/HaruhiYunona/VioletBlue/" style="color:#000;font-size:small;">Github@HaruhiYunona<br>Obj#VioletBlue -MIT License</a>
    </footer>
    <script>
        let fileSelector = '';
        'use strict';

        (function(document, window, index) {
            let inputs = document.querySelectorAll('.inputfile');
            Array.prototype.forEach.call(inputs, function(input) {
                let label = input.nextElementSibling,
                    labelVal = label.innerHTML;

                input.addEventListener('change', function(e) {
                    let fileName = '';
                    fileSelector = this.files[0];
                    if (this.files && this.files.length > 1)
                        fileName = (this.getAttribute('data-multiple-caption') || '').replace('{count}', this.files.length);
                    else
                        fileName = e.target.value.split('\\').pop();

                    if (fileName)
                        label.querySelector('span').innerHTML = fileName;
                    else
                        label.innerHTML = labelVal;
                });

                input.addEventListener('focus', function() {
                    input.classList.add('has-focus');
                });
                input.addEventListener('blur', function() {
                    input.classList.remove('has-focus');
                });
            });

        }(document, window, 0));

        window.onload = function() {

            document.getElementsByClassName('tool_bar')[0].addEventListener('click', () => {
                window.opener = null;
                window.open('', '_self');
                window.close();
            });
            document.getElementsByClassName('mid-button')[0].addEventListener('click', () => {
                alertify.set('notifier', 'position', 'top-center');
                alertify.set('notifier', 'delay', 2);
                if (load_flag == 0) {
                    loadVisable('上传中');
                    let reader = new FileReader();
                    reader.readAsDataURL(fileSelector);
                    reader.onload = function(e) {
                        post('../api/uploadFile', {
                            passWd: $_COOKIE('passWd'),
                            file: e.target.result.replace(/=/g, '@').replace(/\//g, '_').replace(/\+/g, '-'),
                            type: <?php echo "'" . $type . "'\n" ?>
                        }).then((response) => {
                            loadHide();
                            if (response.code == 1) {
                                alertify.success("上传成功");
                                setTimeout(() => {
                                    window.opener = null;
                                    window.open('', '_self');
                                    window.close();
                                }, 1000);
                            } else {
                                alertify.error("上 传 失 败");
                            }
                        });
                    }
                }
            });
        }
    </script>
</body>