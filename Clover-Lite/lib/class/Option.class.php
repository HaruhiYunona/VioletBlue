<?php

namespace CloverLite;



/**
 * 网站设置类
 */
class Option
{



    /**
     * 获取网站配置信息
     * @return array
     */
    public static function info()
    {
        return [
            'title' => SITE_TITLE,
            'detail' => SITE_DETAIL,
            'icp' => ICP_FILINGS,
            'net' => NETWORK_FILINGS,
            'mid' => VUP_MID,
            'nickname' => VTB_NICKNAME,
            'level' => VTB_LEVEL,
            'following' => VTB_FOLLOWING,
            'follower' => VTB_FOLLOWER,
            'sign' => VTB_SIGN,
            'api_cache_time' => API_CACHE_TIME
        ];
    }


    /**
     * 修改网站配置
     * @param array $config 配置表单
     * @return boolean
     */
    public static function change($config)
    {
        $path = __DIR__ . '/../..' . CONF_DIR . '/Config_site.inc.php';
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



    /**
     * 修改网站功能设置
     * @param array $config 配置表单
     * @return boolean
     */
    public static function setting($config)
    {
        $passwd = md5($config['passWd']);
        $notice = str_replace("\n", "<br>", $config['notice']);
        $tags = str_replace('|全部|', "", "|" . join("||", json_decode($config['tags'], true)) . "|");
        Db::exec(Module::option($passwd, $notice, $tags));
        return true;
    }



    /**
     * 管理密码
     * @return string
     */
    private static function passWd()
    {
        return Db::query(Module::passWd(), true);
    }



    /**
     * 鉴权
     * @param string $passwd 密码
     * @return boolean
     */
    public static function passport($passwd)
    {
        return (md5($passwd) === self::passWd()) ? true : false;
    }
}
