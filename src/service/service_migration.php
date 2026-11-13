<?php
declare(strict_types=1);
namespace VioletBlue\service;

use VioletBlue\model\{model_database, model_migration};
use VioletBlue\support\support_song_lock;

/** 预览重复歌曲，按去重或合并规则迁移旧数据库。 */
final class service_migration
{
    private readonly model_migration $model;

    public function __construct() { $this->model = new model_migration(model_database::connection()); }

    /** 列出可访问的歌单库；返回：数据库与表前缀。 */
    public function databases(): array
    {
        return array_map(fn(array $target): array => $target + ['current' => $this->isCurrent($target)], $this->model->candidates());
    }

    /** 读取数据库与重复项，生成两种处理结果；入参：数据库、前缀；返回：预览。 */
    public function preview(string $database, string $prefix): array
    {
        $target = $this->model->target($database, $prefix);
        $snapshot = $this->model->snapshot($target);
        $plan = self::plan($snapshot['song']);
        return $target + ['current' => $this->isCurrent($target), 'fingerprint' => $this->fingerprint($snapshot),
            'duplicate_count' => count($snapshot['song']) - count($plan['deduplicate']), 'remaining' => count($plan['deduplicate']), 'groups' => $plan['groups']];
    }

    /** 处理重复项并升级结构；入参：目标、模式、预览指纹；返回：迁移结果。 */
    public function apply(string $database, string $prefix, string $mode, string $fingerprint): array
    {
        if (!in_array($mode, ['deduplicate', 'merge'], true)) throw new \InvalidArgumentException('请选择去重迁移或合并迁移');
        return support_song_lock::run(function () use ($database, $prefix, $mode, $fingerprint): array {
            $target = $this->model->target($database, $prefix);
            $snapshot = $this->model->snapshot($target);
            if (!hash_equals($this->fingerprint($snapshot), $fingerprint)) throw new \DomainException('歌单内容已变化，请重新预览后再迁移');
            if ($this->isCurrent($target)) (new service_import())->cancelAll();
            $plan = self::plan($snapshot['song']);
            $backups = $this->model->replace($target, $snapshot, $plan[$mode]);
            return ['database' => $database, 'prefix' => $prefix, 'total' => count($plan[$mode]),
                'removed' => count($snapshot['song']) - count($plan[$mode]), 'backups' => $backups, 'login_required' => $this->isCurrent($target)];
        });
    }

    /** 按歌名与歌手精确分组，保留最早序号；入参：歌曲行；返回：去重、合并及重复预览。 */
    public static function plan(array $songs): array
    {
        usort($songs, static fn(array $a, array $b): int => (int) $a['uid'] <=> (int) $b['uid']);
        $buckets = [];
        foreach ($songs as $song) $buckets[serialize([(string) $song['name'], (string) $song['singer']])][] = $song;
        $deduplicate = []; $merge = []; $groups = [];
        foreach ($buckets as $rows) {
            $first = $rows[0]; $combined = $first; $tags = []; $notes = [];
            foreach ($rows as $row) {
                foreach (service_playlist::decodeTags((string) $row['type']) as $tag) {
                    if (!in_array($tag, $tags, true)) $tags[] = $tag;
                }
                $note = trim((string) $row['note']);
                if ($note !== '' && !in_array($note, $notes, true)) $notes[] = $note;
                foreach ($row as $field => $value) {
                    if (in_array($field, ['uid','name','singer','type','note'], true)) continue;
                    if (self::blank($combined[$field] ?? null, $field) && !self::blank($value, $field)) $combined[$field] = $value;
                }
            }
            $combined['type'] = json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $combined['note'] = implode('，', $notes);
            $deduplicate[] = $first; $merge[] = $combined;
            if (count($rows) > 1) $groups[] = ['name' => (string) $first['name'], 'singer' => (string) $first['singer'],
                'rows' => array_map(self::display(...), $rows), 'merged' => self::display($combined)];
        }
        return ['deduplicate' => $deduplicate, 'merge' => $merge, 'groups' => $groups];
    }

    /** 将记录转换为前端预览；入参：歌曲行；返回：展示字段。 */
    private static function display(array $row): array
    {
        return ['uid' => (int) $row['uid'], 'name' => (string) $row['name'], 'singer' => (string) $row['singer'],
            'tags' => service_playlist::decodeTags((string) $row['type']), 'note' => (string) $row['note'], 'time' => (int) $row['time']];
    }

    /** 判断可被后续记录补全的空值；入参：值、字段名；返回：是否为空。 */
    private static function blank(mixed $value, string $field): bool
    {
        return $value === null || (is_string($value) && trim($value) === '') || ($field === 'time' && (int) $value === 0);
    }

    /** 判断是否为当前站点；入参：目标；返回：是否匹配。 */
    private function isCurrent(array $target): bool
    {
        $state = model_database::connection()->query('SELECT DATABASE() AS db, @@lower_case_table_names AS folded')->fetch();
        $song = trim(model_database::table('song'), '`');
        return (int) $state['folded'] === 0
            ? $target['database'] === $state['db'] && $target['song_table'] === $song
            : strcasecmp($target['database'], $state['db']) === 0 && strcasecmp($target['song_table'], $song) === 0;
    }

    /** 为预览数据及结构生成指纹；入参：快照；返回：SHA256。 */
    private function fingerprint(array $snapshot): string { return hash('sha256', serialize($snapshot)); }
}
