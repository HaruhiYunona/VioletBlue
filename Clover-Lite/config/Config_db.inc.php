<?php

/**
 * Clover-Lite PHP framework
 * 
 * @package Clover Lite ver PHP
 * @author HaruhiYunona
 * @version 1.0.0
 * @link https://github.com/HaruhiYunona
 * 
 */

/******************************************************************\
 *                CopyRight 2022, HaruhiYunona                    *          
 *                Released under the MIT license                  *
\******************************************************************/

namespace CloverLite;

class ConfigDb
{


    /**
     * 数据库配置文件
     */
    public static function database()
    {


        
        //数据库服务器地址
        defined('DB_SERVER_NAME') ||
            define('DB_SERVER_NAME', 'localhost');



        //数据库用户名
        defined('DB_USER_NAME') ||
            define('DB_USER_NAME', 'root');



        //数据库用户密码
        defined('DB_PASSWORD') ||
            define('DB_PASSWORD', 123456);



        //数据库名
        defined('DB_NAME') ||
            define('DB_NAME', 'VioletBlue');



        //端口
        defined('DB_PORT') ||
            define('DB_PORT', 3306);



        //数据表前缀
        defined('TABLE_PRE_NAME') ||
            define('TABLE_PRE_NAME', 'VB');
    }
}
