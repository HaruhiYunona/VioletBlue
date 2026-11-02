<?php
declare(strict_types=1);
namespace VioletBlue\support;

/** 串行执行当前站点的导入批次与清空操作。 */
final class support_song_lock
{
    /**
     * 持有歌曲写锁时执行回调。
     * @param callable $action 数据库和任务状态操作
     * @return mixed 回调结果
     */
    public static function run(callable $action): mixed
    {
        $handle = fopen(support_config::root() . '/storage/song-write.lock', 'c');
        if ($handle === false) throw new \RuntimeException('无法创建歌单操作锁');
        try {
            if (!flock($handle, LOCK_EX)) throw new \RuntimeException('无法锁定歌单操作');
            return $action();
        } finally {
            flock($handle, LOCK_UN); fclose($handle);
        }
    }
}
