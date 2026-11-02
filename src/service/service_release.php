<?php
declare(strict_types=1);
namespace VioletBlue\service;

use VioletBlue\support\{support_config, support_github, support_version};
use Symfony\Component\Yaml\Yaml;

/** 读取 GitHub 正式 Release，按版本选择累计补丁或安装包。 */
final class service_release
{
    /** 用途：注入下载客户端及站点根目录；入参：客户端、根目录；返回值：版本服务。 */
    public function __construct(private readonly support_github $client = new support_github(), private readonly ?string $root = null) {}

    /** 用途：读取站点布局、版本和更新配置；入参：无；返回值：更新上下文。 */
    public function context(): array
    {
        $root = $this->root ?? support_config::publicRoot();
        $standalone = is_file($root . '/app/bootstrap.php') && is_file($root . '/.package.json');
        $private = $standalone ? $root . '/app' : support_config::root();
        $path = $private . '/src/config/config_update.yaml' . ($standalone ? '.php' : '');
        $config = is_file($path) ? Yaml::parseFile($path) : [];
        $repository = is_array($config) ? ($config['repository'] ?? 'HaruhiYunona/VioletBlue') : '';
        if (!is_string($repository) || !preg_match('~\A[A-Za-z0-9][A-Za-z0-9-]{0,38}/[A-Za-z0-9_.-]{1,100}\z~D', $repository)) throw new \InvalidArgumentException('更新仓库须填写 owner/repository');
        $version = support_version::info()['version'];
        if ($standalone) {
            $metadata = json_decode((string) file_get_contents($root . '/.package.json'), true);
            $version = support_version::normalize(is_array($metadata) ? ($metadata['version'] ?? null) : null);
            if ($version === null || ($metadata['layout'] ?? null) !== 'wwwroot') throw new \RuntimeException('当前安装包的版本标记无效');
        }
        $storage = $private . '/storage/updates';
        if (!is_dir($storage) && !mkdir($storage, 0700, true) && !is_dir($storage)) throw new \RuntimeException('无法创建更新目录');
        return ['root' => $root, 'storage' => $storage, 'version' => $version, 'repository' => $repository,
            'supported' => $standalone, 'cache_seconds' => max(60, min(3600, (int) ($config['cache_seconds'] ?? 300)))];
    }

    /** 用途：筛选正式版本及同版本附件；入参：GitHub Release 列表、仓库；返回值：版本降序列表。 */
    public static function normalize(array $raw, string $repository): array
    {
        $releases = [];
        foreach ($raw as $release) {
            if (!is_array($release) || ($release['draft'] ?? true) !== false || ($release['prerelease'] ?? true) !== false) continue;
            $version = support_version::normalize($release['tag_name'] ?? null);
            if ($version === null) {
                $versions = [];
                foreach (is_array($release['assets'] ?? null) ? $release['assets'] : [] as $asset) {
                    if (!is_array($asset) || !is_string($asset['name'] ?? null) || !preg_match('/\A(?:install|patch)_(v?\d+\.\d+(?:\.\d+)?)\.zip\z/D', $asset['name'], $match)) continue;
                    $candidate = support_version::normalize($match[1]);
                    if ($candidate !== null) $versions[$candidate] = true;
                }
                if (count($versions) === 1) $version = array_key_first($versions);
            }
            if ($version === null) continue;
            $assets = [];
            foreach (is_array($release['assets'] ?? null) ? $release['assets'] : [] as $asset) {
                if (!is_array($asset) || !is_string($asset['name'] ?? null) || !preg_match('/\A(install|patch)_(v?\d+\.\d+(?:\.\d+)?)\.zip\z/D', $asset['name'], $match)) continue;
                $url = $asset['browser_download_url'] ?? '';
                if (support_version::normalize($match[2]) !== $version || !is_string($url) || !str_starts_with($url, 'https://github.com/' . $repository . '/releases/download/')) continue;
                $digest = $asset['digest'] ?? '';
                $assets[$match[1]] = ['name' => $asset['name'], 'url' => $url, 'size' => is_int($asset['size'] ?? null) ? $asset['size'] : 0,
                    'sha256' => is_string($digest) && preg_match('/\Asha256:([a-f0-9]{64})\z/D', $digest, $hash) ? $hash[1] : ''];
            }
            $releases[] = ['version' => $version, 'title' => is_string($release['name'] ?? null) && $release['name'] !== '' ? $release['name'] : $version,
                'description' => is_string($release['body'] ?? null) ? $release['body'] : '', 'published_at' => (string) ($release['published_at'] ?? ''),
                'url' => 'https://github.com/' . $repository . '/releases/tag/' . rawurlencode((string) $release['tag_name']), 'assets' => $assets];
        }
        usort($releases, static fn(array $a, array $b): int => version_compare($b['version'], $a['version']));
        return $releases;
    }

    /** 用途：检查正式 Release 并生成自动更新计划；入参：是否刷新缓存；返回值：前端版本信息。 */
    public function check(bool $force = false): array
    {
        $context = $this->context();
        $cachePath = $context['storage'] . '/releases-' . hash('sha256', $context['repository']) . '.json';
        $cached = is_file($cachePath) ? json_decode((string) file_get_contents($cachePath), true) : null;
        $releases = null;
        if (!$force && is_array($cached) && ($cached['time'] ?? 0) > time() - $context['cache_seconds'] && is_array($cached['releases'] ?? null)) $releases = $cached['releases'];
        if ($releases === null) {
            $raw = [];
            for ($page = 1; $page <= 5; $page++) {
                $items = $this->client->request('https://api.github.com/repos/' . $context['repository'] . '/releases?per_page=100&page=' . $page);
                if (!array_is_list($items)) throw new \RuntimeException('GitHub Release 列表无效', 502);
                $raw = [...$raw, ...$items];
                if (count($items) < 100) break;
            }
            $releases = self::normalize($raw, $context['repository']);
            support_config::atomic($cachePath, json_encode(['time' => time(), 'releases' => $releases], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
        $latest = $releases[0] ?? null;
        $newer = $latest !== null && version_compare($latest['version'], $context['version'], '>');
        $asset = $latest['assets']['patch'] ?? $latest['assets']['install'] ?? null;
        $reason = !$newer ? ($latest === null ? '未找到符合版本号规则的正式 Release' : '当前已是最新版本')
            : (!$context['supported'] ? '源码部署请下载更新包后手动更新；自动更新适用于 wwwroot 安装包布局'
                : ($asset === null ? '该 Release 尚未上传对应版本的更新包'
                    : ($asset['sha256'] === '' ? '附件缺少 GitHub SHA256 校验值，请重新上传附件' : (!class_exists(\ZipArchive::class) ? '服务器需要启用 PHP zip 扩展' : '可自动更新'))));
        return ['current' => $context['version'], 'repository' => $context['repository'], 'supported' => $context['supported'],
            'available' => $newer, 'can_update' => $newer && $context['supported'] && $asset !== null && $asset['sha256'] !== '' && class_exists(\ZipArchive::class),
            'reason' => $reason, 'latest' => $latest, 'releases' => array_slice($releases, 0, 20), 'checked_at' => time()];
    }
}
