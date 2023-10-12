<?php

namespace CloverLite;



/**
 * 数据库查询模块类
 * @return string sqlstring
 */
class Module
{



    /**
     * 标签列表
     */
    public static function tagList()
    {
        return "SELECT `value` FROM `" . TABLE_PRE_NAME . "_others` WHERE `name` = 'type'";
    }



    /**
     * 歌单列表
     * @param string $tag 标签
     * @param int $paege 页号
     */
    public static function songList($tag, $page)
    {
        $tag = addslashes($tag);
        $pageStart = ($page - 1) * 25;
        return ($tag == '全部') ? "SELECT * FROM `" . TABLE_PRE_NAME . "_song` LIMIT $pageStart,25" : "SELECT * FROM `" . TABLE_PRE_NAME . "_song` WHERE `type` LIKE '%" . $tag . "%' LIMIT " . $pageStart . ",25";
    }



    /**
     * 公告
     */
    public static function notice()
    {
        return "SELECT `value` FROM `" . TABLE_PRE_NAME . "_others` WHERE `name` = 'notice'";
    }



    /**
     * 密码
     */
    public static function passWd()
    {
        return "SELECT `value` FROM `" . TABLE_PRE_NAME . "_others` WHERE `name` = 'passWd'";
    }



    /**
     * 搜索
     * @param string $search 搜索内容
     */
    public static function search($search)
    {
        $search=addslashes($search);
        return "SELECT * FROM `" . TABLE_PRE_NAME . "_song` WHERE `name` LIKE '%" . $search . "%' OR `singer` LIKE '%" . $search . "%' OR `note` LIKE '%" . $search . "%' OR `uid` = '" . $search . "'";
    }



    /**
     * 随机选歌
     */
    public static function random()
    {
        return "SELECT * FROM `" . TABLE_PRE_NAME . "_song` ORDER BY RAND() LIMIT 1";
    }



    /**
     * 歌曲详情
     * @param string $uid 歌曲UID
     */
    public static function songInfo($uid)
    {
        return "SELECT * FROM `" . TABLE_PRE_NAME . "_song` WHERE `uid` = {$uid}";
    }



    /**
     * 检查歌曲重名
     * @param string $name 歌曲名
     * @param string $singer 歌手名
     */
    public static function check($name, $singer)
    {
        $name=addslashes($name);
        $singer=addslashes($singer);
        return "SELECT * FROM `" . TABLE_PRE_NAME . "_song` WHERE `name` = '{$name}' AND `singer` = '{$singer}'";
    }



    /**
     * 插入歌曲
     * @param string $name 歌曲
     * @param string $singer 歌手
     * @param string $tags 歌曲类型
     * @param string $claim 歌曲冠名
     * @param string $time 添加时间
     */
    public static function addOne($name, $singer, $tags, $claim, $time)
    {
        list($name,$singer,$tags,$claim)=[addslashes($name),addslashes($singer),addslashes($tags),addslashes($claim)];
        return "INSERT INTO `" . TABLE_PRE_NAME . "_song` (`uid`, `name`, `singer`, `type`, `note`, `time`) VALUES (NULL, '{$name}', '{$singer}', '{$tags}', '{$claim}', '{$time}');";
    }



    /**
     * 删除歌曲
     * @param int $uid uid
     */
    public static function delete($uid)
    {
        return "DELETE FROM `" . TABLE_PRE_NAME . "_song` WHERE `" . TABLE_PRE_NAME . "_song`.`uid` = {$uid}";
    }



    /**
     * 修改歌曲
     * @param int $uid UID
     * @param string $name 歌名
     * @param string $singer 歌手
     * @param string $tags 歌曲类型
     * @param string $claim 歌曲冠名
     */
    public static function edit($uid, $name, $singer, $tags, $claim)
    {
        list($name,$singer,$tags,$claim)=[addslashes($name),addslashes($singer),addslashes($tags),addslashes($claim)];
        return "UPDATE `" . TABLE_PRE_NAME . "_song` SET `name` = '{$name}', `singer` = '{$singer}', `type` = '{$tags}', `note` = '{$claim}' WHERE `" . TABLE_PRE_NAME . "_song`.`uid` = {$uid}";
    }



    /**
     * 修改功能设置
     * @param string $passWd 密码
     * @param string $notice 公告
     * @param string $tags 歌曲类型
     */
    public static function option($passWd, $notice, $tags)
    {
        $notice=addslashes($notice);
        $tags=addslashes($tags);
        return "UPDATE  `" . TABLE_PRE_NAME . "_others` SET `value` = '" . $notice . "' WHERE  `" . TABLE_PRE_NAME . "_others`.`name` = 'notice';
        UPDATE  `" . TABLE_PRE_NAME . "_others` SET `value` = '" . $tags . "' WHERE  `" . TABLE_PRE_NAME . "_others`.`name` = 'type';
        UPDATE  `" . TABLE_PRE_NAME . "_others` SET `value` = '" . $passWd . "' WHERE  `" . TABLE_PRE_NAME . "_others`.`name` = 'passWd';";
    }
}
