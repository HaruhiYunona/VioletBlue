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

class ConfigSite
{
    public static function op()
    {



        //框架根目录
        defined('ROOT') ||
            define('ROOT', '/');



        //配置文件目录
        defined('CONF_DIR') ||
            define('CONF_DIR', '/config');



        //缓存文件目录
        defined('CACHE_DIR') ||
            define('CACHE_DIR', '/cache');



        //版本文件目录
        defined('ENV_DIR') ||
            define('ENV_DIR', '/env');



        //核心文件目录
        defined('LIB_DIR') ||
            define('LIB_DIR', '/lib');
    }
    public static function site()
    {



        //网站标题
        defined('SITE_TITLE') ||
            define('SITE_TITLE', '星川-一个技术Up');



        //网站介绍
        defined('SITE_DETAIL') ||
            define('SITE_DETAIL', '欢迎来到我的歌单站');



        //ICP备案号.国内服务器需要
        defined('ICP_FILINGS') ||
            define('ICP_FILINGS', '');



        //网安备案号.国内服务器需要
        defined('NETWORK_FILINGS') ||
            define('NETWORK_FILINGS', '');



        //VUP MID，电脑网页打开空间，https://space.bilibili.com/104092258?spm_id_from的104092258这段就是
        defined('VUP_MID') ||
            define('VUP_MID', 104092258);



        //VTuber昵称
        defined('VTB_NICKNAME') ||
            define('VTB_NICKNAME', 'BLUEVIOLETER');



        //VTuber等级
        defined('VTB_LEVEL') ||
            define('VTB_LEVEL', 1);



        //VTuber关注
        defined('VTB_FOLLOWING') ||
            define('VTB_FOLLOWING', 1);



        //VTuber粉丝
        defined('VTB_FOLLOWER') ||
            define('VTB_FOLLOWER', 1);



        //VTuber签名介绍
        defined('VTB_SIGN') ||
            define('VTB_SIGN', 'NO Bio yet.');



        //api缓存时间
        defined('API_CACHE_TIME') ||
            define('API_CACHE_TIME', 3600);
    }
}