<?php

namespace CloverLite;


class Autoloader
{
    public static function loader($class)
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        if (strstr($path, DIRECTORY_SEPARATOR) !== false) {
            $namespace = substr($path, 0, strrpos($path, DIRECTORY_SEPARATOR));
            $class = substr($path, strripos($path, DIRECTORY_SEPARATOR) + 1);
            if ($namespace == 'CloverLite') {
                //引入配置文件和类文件
                require_once __DIR__ . '/../config/Config_db.inc.php';
                require_once __DIR__ . '/../config/Config_site.inc.php';
                ConfigDb::database();
                ConfigSite::op();
                ConfigSite::site();
                require_once __DIR__ . '/class/' . $class . '.class.php';
            } else {
                $file = __DIR__ . '/../res/' . $class . 'class.php';
                if (file_exists($file)) {
                    require_once $file;
                    (!class_exists($namespace . '\\' . $class)) && die('not found class');
                } else {
                    die('not found file');
                }
            }
        }
    }
}

spl_autoload_register('CloverLite\\Autoloader::loader');
