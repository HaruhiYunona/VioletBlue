<?php

namespace CloverLite;



/**
 * 请求类
 */
class Request
{



    /**
     * 获取URL指向路径
     * @return string
     */
    public static function path()
    {
        $request = str_replace("/api/", "", $_SERVER['REQUEST_URI']);
        if (strstr($request, '?') !== false) {
            $request = substr($request, 0, strrpos($request, '?'));
        }
        return $request;
    }



    /**
     * 获取请求内容
     * @param string $name 需获取请求名
     * @param string $method 需获取请求类型(可选GET/POST)
     * @return mixed;
     */
    public static function get($name, $method = 'POST')
    {
        $request = (strtoupper($method) == "GET") ? @$_SERVER['REQUEST_URI'] : '?' . @file_get_contents('php://input');
        $request = explode('&', substr($request, strripos($request, '?') + 1));
        foreach ($request as $row) {
            preg_match('/^.*(?=\=)/', $row, $reqName);
            preg_match('/(?<=\=).*$/', $row, $reqValue);
            isset($reqName[0]) or $reqName[0] = null;
            isset($reqValue[0]) or $reqValue[0] = null;
            $reqs[$reqName[0]] = urldecode($reqValue[0]);
        }

        return $reqs[$name];
    }



    /**
     * 获取所有请求
     * @param string $method 需请求类型(默认为AUTO,可选POST/GET/AUTO. AUTO即自动叠加GET与POST请求. 如果GET内已定义,POST的内容将不会被应用)
     * @return array
     */
    public static function allGet($method = 'POST')
    {
        $request = (strtoupper($method) == "GET") ? $_SERVER['REQUEST_URI'] : '?' . @file_get_contents('php://input');
        $request = explode('&', substr($request, strripos($request, '?') + 1));
        foreach ($request as $row) {
            preg_match('/^.*(?=\=)/', $row, $reqName);
            preg_match('/(?<=\=).*$/', $row, $reqValue);
            isset($reqName[0]) or $reqName[0] = null;
            isset($reqValue[0]) or $reqValue[0] = null;
            $reqs[$reqName[0]] = $reqValue[0];
        }
        return $reqs;
    }



    /**
     * CURL获取网页内容
     * @param string $link 网页链接
     * @param boolean $zip 是否启用GZIP压缩解析
     * @return mixed
     */
    public static function curlGet($url, $zip = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, trim($url));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5000);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($zip) {
            curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
        }
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;
    }



    /**
     * 接收文件
     * @param $file 文件流
     * @param $path 保存文件目录路径
     * @param $filename 文件名
     * @param $type 文件类型
     */
    public static function recvFile($file, $path, $filename, $type)
    {
        if ($file == null && $file == "") {
            return ['result' => 0, 'message' => '文件流为空.'];
        }
        $file = substr($file, strrpos($file, "base64,") + 7);
        $tmpPath = __DIR__ . '/../..' . CACHE_DIR . '/upload-cache-' . mt_rand(100000, 999999) . '.tmp';
        $fileStream = base64_decode($file);
        $bit = file_put_contents($tmpPath, $fileStream);
        $fileSize = filesize($tmpPath) / 1000;
        if ($bit <= 0) {
            unlink($tmpPath);
            return ['result' => 0, 'message' => '创建缓存文件失败.'];
        }
        if ($fileSize > 2048 && $fileSize < 1) {
            unlink($tmpPath);
            return ['result' => 0, 'message' => '文件不能为空或者超过2MB'];
        }
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        unlink($tmpPath);
        if (!stristr($mimetype, $type)) {
            return ['result' => 0, 'message' => '文件类型不正确'];
        }
        $filePath = $path . '/' . $filename;
        $versionPath = __DIR__ . '/../..' . ENV_DIR . '/' . $filename . ".txt";
        $versionFile = fopen($versionPath, 'w');
        fwrite($versionFile, time());
        fclose($versionFile);
        $bit = file_put_contents($filePath, $fileStream);
        return ($bit <= 0) ? ['result' => 0, 'message' => '上传文件失败.'] : ['result' => 1, 'message' => '上传文件成功.'];
    }
}
