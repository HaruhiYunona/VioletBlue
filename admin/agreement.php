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
        <div class="title" style="margin-bottom:30px">用户协议</div>
        <span class="lastest_version">MIT LICENSE</span>
        <p style="margin-bottom: 30px;">
            Copyright (c) 2023 樱樱怪(HaruhiYunona)<br><br>

            Permission is hereby granted, free of charge, to any person obtaining a copy
            of this software and associated documentation files (the "Software"), to deal
            in the Software without restriction, including without limitation the rights
            to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
            copies of the Software, and to permit persons to whom the Software is
            furnished to do so, subject to the following conditions:<br><br>

            The above copyright notice and this permission notice shall be included in all
            copies or substantial portions of the Software.<br><br>

            THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
            IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
            FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
            AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
            LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
            OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
            SOFTWARE.
        </p>
        <span class="lastest_version">用户公约</span>
        <p>
            1.本网站源代码为开源免费使用,保证无违规内容。您可自行改动网站代码文件,但不得发布有违公序良俗和法律有关内容。<br><br>
            2.用户自行改动代码、网站内容不代表作者意见,网站内容与作者无关，请自行为自己的行为负责。<br><br>
            3.严格遵守开源协议, 执行开源协议内容。<br><br>
            4.版权归属本作者HaruhiYunona所有,禁止直接盗版。如要进行改版开源请至代码仓库fork项目(包括为主播定制)。<br><br>
            5.有意见请至代码仓库提出issue或发送邮件,请勿随意抹黑造谣本项目内容。<br><br>
            6.如您有意协助本项目开发,请用邮件联系本作者,经审核后会为您添加权限及作者声明。<br><br>
            7.本作者保留本代码项目一切合法权益。<br><br>
        </p>
    </div>
    <footer style="margin-bottom:50px;width:100%;text-align:center">
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