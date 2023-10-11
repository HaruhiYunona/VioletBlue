<?php
function is_mobile_request()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) $mobile_browser++;
    if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false)) $mobile_browser++;
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) $mobile_browser++;
    if (isset($_SERVER['HTTP_PROFILE'])) $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
    $mobile_agents = array('w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac', 'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno', 'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-', 'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-');
    if (in_array($mobile_ua, $mobile_agents)) $mobile_browser++;
    if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false) $mobile_browser++;
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false) $mobile_browser = 0;
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false) $mobile_browser++;
    if ($mobile_browser > 0) {
        return true;
    } else {
        return false;
    }
}

function is_installer()
{
    return !file_exists('./install/.block');
}


if (is_installer()) {
    header("Location:./install/installer.php");
}

$siteInfo = json_decode(file_get_contents('http://' . @$_SERVER['HTTP_HOST'] . '/api/siteInfo'), true);

if (is_mobile_request()) { ?>
    <!DOCTYPE html>

    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
        <meta name="referrer" content="no-referrer">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
        <meta name="description" content="<?php echo $siteInfo['content']['detail']; ?>">
        <link rel="shortcut icon" href="./favicon.ico">
        <title><?php echo $siteInfo['content']['title']; ?></title>
        <script src="./static/js/script.min.js"></script>
        <link rel="stylesheet" href="./static/css/alertify.min.css" />
        <script src="./static/js/alertify.min.js"></script>
        <link rel="stylesheet" type="text/css" href="./static/css/m_style.min.css">
    </head>

    <?php require_once("./static/html/mobile_body.html");
    if (!empty($siteInfo['content']['icp']) || !empty($siteInfo['content']['net'])) {
        echo '<p>';
    }
    if (!empty($siteInfo['content']['icp'])) {
        echo '<li><a href="https://beian.miit.gov.cn/" target="_blank">' . $siteInfo['content']['icp'] . '</a></li>';
    }
    if (!empty($siteInfo['content']['icp']) && !empty($siteInfo['content']['net'])) {
        echo '<li>|</li>';
    }
    if (!empty($siteInfo['content']['net'])) {
        echo '<li><img src="./static/img/icon.png" style="width: 12px;height: 12px;"><a href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=' . preg_replace("/[^0-9]/", "", $siteInfo['content']['net']) . '" target="_blank">' . $siteInfo['content']['net'] . '</a></li>';
    }
    if (!empty($siteInfo['content']['icp']) || !empty($siteInfo['content']['net'])) {
        echo '</p>';
    }
    require_once("./static/html/mobile_footer.html");
} else { ?>
    <!DOCTYPE html>

    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
        <meta name="referrer" content="no-referrer">
        <meta name="description" content="<?php echo $siteInfo['content']['detail']; ?>">
        <link rel="shortcut icon" href="./favicon.ico">
        <title><?php echo $siteInfo['content']['title']; ?></title>
        <script src="./static/js/script.min.js"></script>
        <link rel="stylesheet" href="./static/css/alertify.min.css" />
        <script src="./static/js/alertify.min.js"></script>
        <link rel="stylesheet" type="text/css" href="./static/css/style.min.css">
    </head>
<?php require("./static/html/pc_body.html");
    if (!empty($siteInfo['content']['icp'])) {
        echo '<li>|</li><li><a href="https://beian.miit.gov.cn/" target="_blank">' . $siteInfo['content']['icp'] . '</a></li>';
    }
    if (!empty($siteInfo['content']['net'])) {
        echo '<li>|</li><li><img src="./static/img/icon.png" style="width: 15px;height: 15px;"><a href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=' . preg_replace("/[^0-9]/", "", $siteInfo['content']['net']) . '" target="_blank">' . $siteInfo['content']['net'] . '</a></li>';
    }
    require_once("./static/html/pc_footer.html");
}
