<?php

namespace CloverLite;



/**
 * 网站写入数据库类
 */
class Push
{



    /**
     * 前置处理函数
     * @param array $song 歌曲信息
     * @return array
     */
    private static function prepare($song)
    {
        $name = trim($song['name']);
        $singer = trim($song['singer']);
        $claim = trim($song['claim']);
        $tags = json_decode($song['tags'], true);
        if (!Db::validator([
            'name' => $name,
            'singer' => $singer,
            'tags' => $tags
        ], [
            'name' => 'BETWEEN 1,50',
            'singer' => 'BETWEEN 1,50',
            'tags' => 'ARRAY'
        ])) {
            return ['result' => false, 'message' => '《' . $name . '》-' . $singer . '参数校验不通过'];
        }
        if (Reader::checkSong($name, $singer)) {
            return ['result' => false, 'message' => '《' . $name . '》-' . $singer . '重复添加'];
        }
        $tagString = '';
        foreach ($tags as $tag) {
            if ($tag != null) {
                $tagString = $tagString . '||' . $tag;
            }
        }
        $tagString = '|' . ltrim($tagString, "||") . '|';
        return ($tagString == '||') ? ['result' => false, 'message' => '《' . $name . '》-' . $singer . '需要填写标签'] : [
            'result' => true,
            'name' => $name,
            'singer' => $singer,
            'tags' => $tagString,
            'claim' => $claim,
            'time' => time()
        ];
    }

    /**
     * 增加歌曲
     * @param string $name 歌名
     * @param string $singer 歌手
     * @param array $tags 标签
     * @param string $claim 冠名
     */
    public static function aSong($name = '', $singer = '', $tags = null, $claim = '')
    {
        $param = self::prepare(['name' => $name, 'singer' => $singer, 'tags' => $tags, 'claim' => $claim]);
        if (!$param['result']) {
            return false;
        }
        return (Db::exec(Module::addOne($param['name'], $param['singer'], $param['tags'], $param['claim'], $param['time'])) < 1) ? false : true;
    }



    /**
     * 批量增加歌曲
     * @param array $songList 歌曲列表
     */
    public static function Songs($songList)
    {
        $songCount = count($songList);
        $sql = '';
        if ($songCount > 500) {
            return ['result' => 0, 'message' => '一次性增加太多歌曲了!每次数量应低于500行'];
        }
        foreach ($songList as $song) {
            $param = self::prepare($song);
            if (!$param['result']) {
                return ['result' => 0, 'message' => '检查不通过！请检查。<br>问题原因:' . $param['message']];
            }
            $sql = $sql . Module::addOne($param['name'], $param['singer'], $param['tags'], $param['claim'], $param['time']);
        }
        $result = Db::exec($sql);
        return ($result < 1) ? ['result' => 0, 'message' => '歌曲列表入库时出现问题'] : ['result' => 1, 'message' => '成功入库歌曲。<br>共计：' . $songCount . '首'];
    }



    /**
     * 删除歌曲
     * @param int $uid 歌曲UID
     */
    public static function delete($uid)
    {
        return (Db::exec(Module::delete($uid)) >= 1 ? true : false);
    }



    /**
     * 编辑歌曲
     * @param int $uid UID
     * @param string $name 歌名
     * @param string $singer 歌手
     * @param string $tags 歌曲类型
     * @param string $claim 歌曲冠名
     */
    public static function edit($uid, $name, $singer, $tags, $claim)
    {
        $uid = (Request::get('uid') != null) ? Request::get('uid') : 0;
        $result = Reader::detail($uid)[0];
        if ($result['singer'] == $singer) {
            $presinger = 'edit@' . $singer;
        }else{
            $presinger=$singer;
        }
        $param = self::prepare(['name' => $name, 'singer' => $presinger, 'tags' => $tags, 'claim' => $claim]);
        if (!$param['result']) {
            return false;
        }
        if (1 > mb_strlen($singer) || mb_strlen($singer) > 50) {
            return false;
        }
        return (Db::exec(Module::edit($uid, $param['name'], $singer, $param['tags'], $param['claim'], $param['time'])) < 1) ? false : true;
    }
}
