<?php



echo "安装中，请稍等...<br>进度:<br>";

function is_installer()
{
    return file_exists('../install/.block');
}


if (is_installer()) {
    header("Location:../index.php");
}

empty($_POST['db']) and die("Error:#101<br>数据库地址为空");
empty($_POST['dbname']) and die("Error:#102<br>数据库名为空");
empty($_POST['dbuser']) and die("Error:#103<br>数据库用户名为空");
empty($_POST['dbpassWd']) and die("Error:#104<br>数据库密码为空");
empty($_POST['dbport']) and die("Error:#105<br>数据库端口为空");
empty($_POST['tablepre']) and die("Error:#106<br>数据库表前缀为空");

echo "-检查参数完毕,10%<br>-尝试连接数据库<br>";

function connect($db, $dbname, $dbuser, $dbpassword, $dbport)
{
    try {
        return new PDO("mysql:host=" . $db . ";dbname=" . $dbname . ";port=" . $dbport . ";charset=utf8;", $dbuser, $dbpassword);
    } catch (PDOException $e) {
        echo "Error:#201<br>" . $e->getMessage() . "<br>";
    }
}

list($db, $dbName, $dbUser, $dbPassword, $dbPort, $tablePreName) = [$_POST['db'], $_POST['dbname'], $_POST['dbuser'], $_POST['dbpassWd'], $_POST['dbport'], $_POST['tablepre']];
$con = connect($db, $dbName, $dbUser, $dbPassword, $dbPort);
($con == false) and die("Error:#202<br>数据库连接失败");
echo "-数据库连接成功,25%<br>-尝试创建表格<br>";
$sql = "
DROP TABLE IF EXISTS `" . $tablePreName . "_others`;
CREATE TABLE IF NOT EXISTS `" . $tablePreName . "_others` (
  `name` varchar(200) NOT NULL,
  `value` varchar(200) NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `" . $tablePreName . "_song` (
    `uid` int NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `singer` varchar(200) NOT NULL,
    `type` text NOT NULL,
    `note` text NOT NULL,
    `time` int NOT NULL,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `uid` (`uid`)
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb3;

INSERT INTO `" . $tablePreName . "_others` (`name`, `value`) VALUES
('notice', '暂无公告喵~'),
('type', '|舰长||流行||古风||粤语||日语||英文|'),
('passWd', 'e10adc3949ba59abbe56e057f20f883e');
DROP TABLE IF EXISTS `" . $tablePreName . "song`;

";
$con->exec($sql);
function isTableExist($con, $name)
{
    $result = $con->query('SHOW TABLES LIKE "' . $name . '"');
    return ($result->fetch() == false) ? true : false;
}

isTableExist($con, $tablePreName . '_others') and die("Error:#203<br>创建表'others'失败");
isTableExist($con, $tablePreName . '_song') and die("Error:#204<br>创建表'song'失败");

echo "-创建表格成功,50%<br>-尝试写入配置文件<br>";

function change($config)
{
    $path = "../Clover-Lite/config/Config_db.inc.php";
    if (!file_exists($path)) {
        return false;
    }
    $fp = fopen($path, 'r');
    $content = fread($fp, filesize($path));
    fclose($fp);
    foreach ($config as $setting) {
        $regx = "/define\s*\(\s*['\"]" . $setting['name'] . "['\"]\s*,\s*(['\"]\s*" . $setting['orgin'] . "\s*['\"]|\d+)\s*\);/";
        $content = preg_replace($regx, "define('" . $setting['name'] . "', " . (is_numeric($setting['new']) ? $setting['new'] : '\'' . $setting['new'] . '\'') . ");", $content);
    }
    try {
        $fp = fopen($path, 'w');
        $result = fwrite($fp, $content, strlen($content));
    } finally {
        fclose($fp);
    }
    return ($result >= 1) ? true : false;
}

$result = change([
    [
        'name' => 'DB_SERVER_NAME',
        'orgin' => 'localhost',
        'new' => $db
    ], [
        'name' => 'DB_USER_NAME',
        'orgin' => 'root',
        'new' => $dbUser
    ], [
        'name' => 'DB_PASSWORD',
        'orgin' => 123456,
        'new' => $dbPassword
    ], [
        'name' => 'DB_NAME',
        'orgin' => 'VioletBlue',
        'new' => $dbName
    ], [
        'name' => 'DB_PORT',
        'orgin' => 3306,
        'new' => $dbPort
    ], [
        'name' => 'TABLE_PRE_NAME',
        'orgin' => 'VB',
        'new' => $tablePreName
    ]
]);

(!$result) and die("Error:#301<br>写入配置文件失败");

echo "-创建表格成功,75%<br>-尝试写入安装锁死文件<br>";

$path = "./.block";
$content = "true";
try {
    $fp = fopen($path, 'w');
    $result = fwrite($fp, $content, strlen($content));
    (!$result) and die("Error:#401<br>写入安装锁死文件失败");
} finally {
    fclose($fp);
}

function getRandChar($length = 8)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}


$path = "../Clover-Lite/env/code.json";
if (!file_exists($path)) {
    $randID = getRandChar(18);
    $content = json_encode([
        'code' => 1,
        'id' => date("YHmids") . $randID
    ]);

    try {
        $fp = fopen($path, 'w');
        $result = fwrite($fp, $content, strlen($content));
        (!$result) and die("Error:#402<br>写入ID失败");
    } finally {
        fclose($fp);
    }
}

function curlGet($url, $zip = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, trim($url));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'));
    curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, -1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if ($zip) {
        curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
    }
    $return = curl_exec($ch);
    curl_close($ch);
    return $return;
}

$php_version = PHP_VERSION;
$os_version = PHP_OS;
$server_ip = $_SERVER['SERVER_ADDR'];
$version = json_decode(file_get_contents('../Clover-Lite/env/version.json'), true);
$randID=json_decode(file_get_contents('../Clover-Lite/env/code.json'), true)['id'];
curlGet('https://api.mdzz.pro/VioletBlue/get/push?rid=' . $randID . "&ip=" . $server_ip . "&php=" . $php_version . "&os=" . $os_version . "&ver=" . $version['version']);
echo 'https://api.mdzz.pro/VioletBlue/get/push?rid=' . $randID . "&ip=" . $server_ip . "&php=" . $php_version . "&os=" . $os_version . "&ver=" . $version['version'];



unlink('./installer.php');
unlink('./installRun.php');

header("Location:./installSus.php");
