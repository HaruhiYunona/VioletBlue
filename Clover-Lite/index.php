<?php

namespace CloverLite;

/**
 * CloverPHP framework Lite suit ver 
 * 
 * @package CloverPHP Lite Version
 * @author HaruhiYunona
 * @version 1.0.0
 * @link https://github.com/HaruhiYunona
 * 
 */

header('Access-Control-Allow-Headers: *');
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/lib/Autoloader.php';

//检测PHP版本
version_compare(PHP_VERSION, '5.6.0', '<') and die('require PHP > 5.6.0 !');



//启动框架
class index
{
    public static function run()
    {
        Handler::io();
    }
}

index::run();
