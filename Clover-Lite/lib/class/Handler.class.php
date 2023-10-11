<?php

namespace CloverLite;



/**
 * 逻辑处理类
 */
class Handler
{



    private static $filetype = [
        'face' => [
            'mime_type' => 'image',
            'path' => '../static/img',
            'file_name' => 'face.png'
        ],
        'favicon' => [
            'mime_type' => 'image',
            'path' => '../',
            'file_name' => 'favicon.ico'
        ],
        'background' => [
            'mime_type' => 'image',
            'path' => '../static/img',
            'file_name' => 'background.png'
        ],
        'ifont' => [
            'mime_type' => 'font',
            'path' => '../static/font',
            'file_name' => 't_min.ttf'
        ]
    ];


    /**
     * 逻辑处理方法
     * @return void
     */
    public static function io()
    {
        $rqFunction = Request::path();
        $funcName = 'CloverLite\\Handler::' . $rqFunction;
        method_exists('CloverLite\\Handler', $rqFunction) or die('not found Func');
        return $funcName();
    }



    /**
     * 获取标签列表
     * @return json
     */
    private static function getTagList()
    {
        return Respons::json(explode("||", trim('|全部|' . Reader::tagList(), '|')));
    }



    /**
     * 获取歌单列表
     * @return json
     */
    private static function getSongList()
    {
        $tag = (Request::get('tag') != null) ? Request::get('tag') : "全部";
        $page = (Request::get('page') != null) ? Request::get('page') : 1;
        return Respons::json(Reader::songList($tag, $page));
    }



    /**
     * 获取公告
     * @return json
     */
    private static function getNotice()
    {
        return Respons::json(Reader::notice());
    }



    /**
     * 搜索
     * @return json
     */
    private static function search()
    {
        $serach = (Request::get('search') != null) ? Request::get('search') : '';
        return Respons::json(Reader::search($serach));
    }



    /**
     * 随机抽取歌曲
     * @return json
     */
    private static function random()
    {
        return Respons::json(Reader::random());
    }



    /**
     * 歌曲详情
     * @return json
     */
    private static function detail()
    {
        $uid = (Request::get('uid') != null) ? Request::get('uid') : 0;
        $result = Reader::detail($uid)[0];
        if (!$result) {
            return Respons::json('not found song detail', false, 0);
        } else {
            return Respons::json([
                'name' => $result['name'],
                'singer' => $result['singer'],
                'tags' => explode("||", trim($result['type'], '|')),
                'claim' => isset($result['note']) ? $result['note'] : ''
            ], false, 1);
        }
    }



    /**
     * Vup信息获取
     * @return json
     */
    private static function vUpInfo()
    {
        return Respons::json(WebGet::vUpInfo(VUP_MID));
    }



    /**
     * 网站信息
     * @return json
     */
    private static function siteInfo()
    {
        return Respons::json(Option::info());
    }



    /**
     * 鉴权
     * @return json
     */
    private static function passport()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        return Respons::json(Option::passport($passWd));
    }



    /**
     * 添加歌曲
     * @return json
     */
    private static function submitSong()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $name = (Request::get('name') != null) ? Request::get('name') : '';
        $singer = (Request::get('singer') != null) ? Request::get('singer') : '';
        $claim = (Request::get('claim') != null) ? Request::get('claim') : '';
        $tags = (Request::get('tags') != null) ? Request::get('tags') : '';
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        if (Reader::checkSong($name, $singer)) {
            return Respons::json("歌曲已存在", false, 0);
        }

        return Respons::json(Push::aSong($name, $singer, $tags, $claim), false, 1);
    }



    /**
     * 批量添加歌曲
     *  @return json
     */
    private static function submitSongs()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $songs = (Request::get('list') != null) ? Request::get('list') : '';
        $songList = json_decode($songs, true);
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        return Respons::json(Push::Songs($songList), false, 1);
    }



    /**
     * 删除歌曲
     *  @return json
     */
    private static function delete()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $uid = (Request::get('uid') != null) ? Request::get('uid') : '';
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        return Respons::json(Push::delete($uid), false, 1);
    }



    /**
     * 修改歌曲
     *  @return json
     */
    private static function edit()
    {
        $uid = (Request::get('uid') != null) ? Request::get('uid') : '';
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $name = (Request::get('name') != null) ? Request::get('name') : '';
        $singer = (Request::get('singer') != null) ? Request::get('singer') : '';
        $claim = (Request::get('claim') != null) ? Request::get('claim') : '';
        $tags = (Request::get('tags') != null) ? Request::get('tags') : '';
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        return Respons::json(Push::edit($uid, $name, $singer, $tags, $claim), false, 1);
    }



    /**
     * 修改网站配置
     *  @return json
     */
    private static function changeOption()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $config = (Request::get('config') != null) ? Request::get('config') : '';
        $config = json_decode($config, true);
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        return Respons::json(Option::change($config));
    }



    /**
     * 修改网站功能设置
     *  @return json
     */
    private static function changeSetting()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $config = (Request::get('config') != null) ? Request::get('config') : '';
        $config = json_decode($config, true);
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        return Respons::json(Option::setting($config));
    }



    /**
     * 上传文件
     *  @return json
     */
    private static function uploadFile()
    {
        $passWd = (Request::get('passWd') != null) ? Request::get('passWd') : '';
        $file = (Request::get('file') != null) ? Request::get('file') : '';
        $file = str_replace('-', '+', str_replace('_', '/', str_replace('@', '=', $file)));
        $type = (Request::get('type') != null) ? Request::get('type') : '';
        if (!Option::passport($passWd)) {
            return Respons::json("您没有权限", false, 0);
        }
        $uploadInfo = self::$filetype[$type];
        return Respons::json(Request::recvFile($file, $uploadInfo['path'], $uploadInfo['file_name'], $uploadInfo['mime_type']));
    }



    /**
     * 检测新版本
     *  @return json
     */
    private static function getUpdate()
    {
        echo Request::curlGet("https://api.mdzz.pro/VioletBlue/get/version");
    }



    /**
     * 获取版本信息
     *  @return json
     */
    private static function getVersion()
    {
        echo file_get_contents(__DIR__ . '/../..' . ENV_DIR . '/version.json');
    }
}
