<?php
declare(strict_types=1);
namespace VioletBlue\service;

use InvalidArgumentException;
use VioletBlue\model\model_playlist;
use VioletBlue\support\support_config;
use VioletBlue\support\support_input;

/** 读取站点资料，保存基础设置、公告、分类与密码。 */
final class service_options
{
    private const MAP = [
        'SITE_TITLE' => 'title', 'SITE_DETAIL' => 'detail', 'ICP_FILINGS' => 'icp', 'NETWORK_FILINGS' => 'net',
        'VUP_MID' => 'mid', 'VTB_NICKNAME' => 'nickname', 'VTB_LEVEL' => 'level',
        'VTB_FOLLOWING' => 'following', 'VTB_FOLLOWER' => 'follower', 'VTB_SIGN' => 'sign', 'API_CACHE_TIME' => 'api_cache_time',
    ];
    private const NUMBERS = ['mid', 'level', 'following', 'follower', 'api_cache_time'];

    /**
     * 读取站点基础配置。
     * @return array 站点字段映射
     */
    public function info(): array
    {
        return support_config::read('site');
    }

    /**
     * 按字段白名单保存基础配置。
     * @param array $changes 包含 name 和 new 的配置项列表
     * @return bool 是否保存成功
     */
    public function change(array $changes): bool
    {
        if (!array_is_list($changes) || count($changes) > count(self::MAP)) {
            throw new InvalidArgumentException('配置格式不正确');
        }
        $config = $this->info();
        foreach ($changes as $change) {
            if (!is_array($change)) {
                throw new InvalidArgumentException('配置项必须为对象');
            }
            $name = support_input::text($change, 'name');
            $key = self::MAP[$name] ?? null;
            if ($key === null) {
                throw new InvalidArgumentException('不允许修改配置：' . $name);
            }
            $value = support_input::text($change, 'new');
            if (mb_strlen($value) > 5000) {
                throw new InvalidArgumentException('配置内容过长');
            }
            $config[$key] = in_array($key, self::NUMBERS, true) ? support_input::integer($value, 0, PHP_INT_MAX) : $value;
        }
        support_config::write('site', $config);
        return true;
    }

    /**
     * 将公告换行保留并转义 HTML。
     * @return string 供前端展示的公告文本
     */
    public function notice(): string
    {
        // 旧公告允许 <br> 换行，其余 HTML 转为纯文本，阻止脚本执行。
        $notice = (new model_playlist())->option('notice');
        $notice = preg_replace('/<br\s*\/?\s*>/i', "\n", $notice) ?? '';
        return nl2br(support_input::escape($notice), false);
    }

    /**
     * 在事务中保存公告、分类及可选的新密码。
     * @param array $config notice、tags、passWd 设置，空密码表示不修改
     * @return bool 是否保存成功
     */
    public function setting(array $config): bool
    {
        $password = support_input::text($config, 'passWd');
        $notice = support_input::text($config, 'notice');
        if (($password !== '' && (mb_strlen($password) < 6 || strlen($password) > 72)) || mb_strlen($notice) > 10000) {
            throw new InvalidArgumentException('密码须为6字以上且不超过72字节，公告最长10000字');
        }
        $tags = service_playlist::normalizeTags(support_input::array($config, 'tags'));
        $model = new model_playlist();
        $model->ensureOptionCapacity();
        $model->transaction(static function () use ($model, $password, $notice, $tags): void {
            if ($password !== '') {
                $model->setOption('passWd', password_hash($password, PASSWORD_DEFAULT));
            }
            $model->setOption('notice', $notice);
            $model->setOption('type', json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        });
        if ($password !== '') {
            service_auth::invalidate();
        }
        return true;
    }
}
