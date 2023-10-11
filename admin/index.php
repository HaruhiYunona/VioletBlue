<?php $siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);
if (!empty($_COOKIE['passWd'])) {
    header('Location: ./home.php');
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo $siteInfo['content']['title'] . "-登录"; ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../static/css/panel.min.css" />
    <script src="../static/js/script.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="stylesheet" href="../static/css/alertify.min.css" />
    <script src="../static/js/alertify.min.js"></script>

</head>

<body>
    <div class="container">
        <div class="login-form">
            <div class="text">登录</div>
            <div class="form-item">
                <input type="password" placeholder="密码" id="passwd">
            </div>
            <div class="btn" id="sub">确定</div>
        </div>
    </div>
    <script>
    </script>
</body>
<script>
    window.onload = function() {
        script();
    }

    const script = () => {
        document.getElementById('sub').addEventListener("click", () => {
            login(document.getElementById('passwd').value)
        });
    }

    const login = (passwd) => {
        if (passwd.length >= 6) {
            post('../api/passport', {
                passWd: passwd
            }, true).then((response) => {
                if (response.code == 1) {
                    setCookie("passWd", passwd, 604800);
                    window.location.replace('./home.php');
                } else {
                    alertify.set('notifier', 'position', 'top-center');
                    alertify.set('notifier', 'delay', 2);
                    alertify.success('登陆失败');
                }

            });

        } else {
            alertify.set('notifier', 'position', 'top-center');
            alertify.set('notifier', 'delay', 2);
            alertify.success('密码不可以为空,不可以低于6个字');
        }
    }
</script>

</html>