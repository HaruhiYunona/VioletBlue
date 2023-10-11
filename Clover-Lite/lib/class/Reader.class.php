<?php

namespace CloverLite;



/**
 * 数据库读取类
 */
class Reader
{



    /**
     * 歌单类别列表
     * @return string
     */
    public static function tagList()
    {
        return Db::query(Module::tagList(), true);
    }



    /**
     * 歌曲列表封装
     * @param array $DbArr 数据库取出的数据;
     * @return array
     */
    private static function songArr($DbArr)
    {
        if ($DbArr == null || $DbArr == false) {
            return false;
        }
        foreach ($DbArr as $row) {
            $list[] = [
                'uid' => $row['uid'],
                'song' => $row['name'],
                'singer' => $row['singer'],
                'type' => explode("||", trim($row['type'], '|')),
                'notice' => $row['note']
            ];
        }
        return $list;
    }



    /**
     * 歌单列表
     * @param string $type 歌单种类
     * @param int $page 页数
     * @return array
     * 
     */
    public static function songList($type, $page)
    {
        $result = Db::query(Module::songList($type, $page));
        return self::songArr($result);
    }



    /**
     * bio公告
     * @return array
     */
    public static function notice()
    {
        return Db::query(Module::notice(), true);
    }



    /**
     * 搜索内容
     * @param string $search 搜索内容
     * @return array
     */
    public static function search($search)
    {
        $result = Db::query(Module::search($search));
        return self::songArr($result);
    }



    /**
     * 随机抽选歌曲
     * @return array
     */
    public static function random()
    {
        $result = Db::query(Module::random());
        return self::songArr($result);
    }



    /**
     * 歌曲信息
     * @param int $uid
     * @return array/boolean
     */
    public static function detail($uid)
    {
        return Db::query(Module::songInfo($uid));
    }



    /**
     * 查重,重复返回true
     * @param string $name 歌名
     * @param string $singer 歌手
     * @return boolean
     */
    public static function checkSong($name, $singer)
    {
        $check = db::query(Module::check($name, $singer));
        return ($check !== false && !empty($check)) ? true : false;
    }
}
