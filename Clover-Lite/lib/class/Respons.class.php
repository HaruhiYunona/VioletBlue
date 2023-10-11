<?php

namespace CloverLite;



/**
 * 响应类
 */
class Respons
{



    /**
     * 返回json参数
     * @param mixed $array 需要返回的数组
     * @param boolean $cache 是否对接口响应内容进行缓存,默认为是
     */
    public static function json(mixed $array,  $cache = true, $status = 1)
    {
        header('content-type:application/json;charset=utf-8');
        if ($cache) {
            $cache_time = API_CACHE_TIME/1;
            $modified_time = @$_SERVER['HTTP_IF_MODIFIED_SINCE'];
            if (strtotime($modified_time) + $cache_time > time()) {
                header("HTTP/1.1 304");
                exit;
            }
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cache_time) . " GMT");
            header("Cache-Control: max-age=" . $cache_time);
        } else {
            header('Expires: Mon, 26 Jul 1970 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
        ($array == false || empty($array)) and $status = 0;
        if (isset($array['result']) && $array['result'] == 0) {
            $status = 0;
            $array = $array['message'];
        }
        if (isset($array['result']) && $array['result'] == 1) {
            $status = 1;
            $array = $array['message'];
        }
        echo json_encode(['code' => $status / 1, 'content' => $array], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
