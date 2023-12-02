<?php

namespace CloverLite;



class WebGet
{



    /**
     * 获取或者从缓存中读取Vup信息
     * @param string $mid Vup mid
     * @return array
     */
    public static function vUpInfo($mid)
    {
        if ($mid !== 0) {
            if (file_exists(__DIR__ . '/../..' . CACHE_DIR . '/vUpInfo.txt')) {
                $cache = json_decode(file_get_contents(__DIR__ . '/../..' . CACHE_DIR . '/vUpInfo.txt'), true);
                if ($cache['time'] >= (time() - 86400) && $cache['mid'] == $mid) {
                    $vInfo = $cache;
                } else {
                    goto getVInfo;
                }
            } else {
                getVInfo:
                $stat = json_decode(Request::curlGet("https://api.bilibili.com/x/relation/stat?vmid=" . $mid), true)['data'];
                $b_page = Request::curlGet('https://space.bilibili.com/' . $mid, true);
                preg_match('/(?<=window.__INITIAL_STATE__=).*?(?=;\(function\(\)\{)/', $b_page, $str);
                $allInfo = json_decode($str[0], true);
                $array = $allInfo['space']['info'];
                if (empty($array['mid'])) {
                    $vInfo = false;
                }
                $vInfo = [
                    'code' => 1,
                    'time' => time(),
                    'mid' => $array['mid'],
                    'name' => $array['name'],
                    'sign' => $array['sign'],
                    'face' => $array['face'],
                    'level' => $array['level'],
                    'follower' => $stat['follower'],
                    'following' => $stat['following']
                ];
                $cacheFile = fopen(__DIR__ . '/../..' . CACHE_DIR . '/vUpInfo.txt', 'w');
                fwrite($cacheFile, json_encode($vInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                fclose($cacheFile);
            }
            return $vInfo;
        } else {
            return [
                'code' => 1,
                'time' => time(),
                'mid' => VUP_MID,
                'name' => VTB_NICKNAME,
                'sign' => VTB_SIGN,
                'face' => './static/img/face.png?v=' . Respons::verCheck('face.png'),
                'level' => VTB_LEVEL,
                'follower' => VTB_FOLLOWER,
                'following' => VTB_FOLLOWING
            ];
        }
    }
}
