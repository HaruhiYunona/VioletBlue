<?php
declare(strict_types=1);
namespace VioletBlue\support;

use RuntimeException;
use ZipArchive;

/** 校验更新 ZIP，只提取可更新的程序文件。 */
final class support_update_package
{
    /** 用途：判断站点数据是否受保护；入参：相对路径；返回值：是否跳过覆盖。 */
    public static function protectedPath(string $path): bool
    {
        $path = strtolower($path);
        return in_array($path, ['favicon.ico', 'install/.block'], true)
            || preg_match('~\A(?:app/src/config|app/storage|static|storage|install)(?:/|\z)~', $path) === 1 && $path !== 'install/.htaccess';
    }

    /** 用途：判断附件中的程序路径是否允许更新；入参：相对路径；返回值：是否允许。 */
    public static function allowed(string $path): bool
    {
        return in_array($path, ['index.php', 'api.php', 'install.php', 'app/bootstrap.php', 'app/.htaccess', 'install/.htaccess', '.package.json', 'LICENSE', 'THIRD_PARTY_NOTICES.md'], true)
            || preg_match('~\A(?:app/src/|app/vendor/|build/|licenses/|admin/)[A-Za-z0-9_.@/+ -]+\z~D', $path) === 1;
    }

    /** 用途：检查目标路径及其父目录，拒绝链接和越界；入参：站点根目录、相对路径；返回值：绝对目标路径。 */
    public static function target(string $root, string $relative): string
    {
        if ($relative === '' || str_contains($relative, '\\') || preg_match('~(?:\A/|[\x00-\x1f\x7f:]|(?:\A|/)\.{1,2}(?:/|\z))~', $relative)) throw new RuntimeException('更新包包含不安全的文件路径');
        $current = rtrim($root, '/\\');
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || str_ends_with($part, '.') || str_ends_with($part, ' ')) throw new RuntimeException('更新包包含不规范路径');
            $current .= '/' . $part;
            if (is_link($current)) throw new RuntimeException('更新目标不能包含符号链接');
        }
        return $current;
    }

    /** 用途：校验包结构及版本并暂存程序文件；入参：ZIP、暂存目录、目标版本；返回值：相对文件路径列表。 */
    public static function stage(string $archive, string $directory, string $version): array
    {
        $zip = new ZipArchive();
        if ($zip->open($archive, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('更新包不是完整的 ZIP 文件');
        try {
            if ($zip->numFiles > 20000) throw new RuntimeException('更新包文件数量超过限制');
            $metadataStat = $zip->statName('.package.json');
            if (!is_array($metadataStat) || $metadataStat['size'] > 65536) throw new RuntimeException('更新包版本标记缺失或过大');
            $metadata = $zip->getFromName('.package.json');
            $package = is_string($metadata) ? json_decode($metadata, true) : null;
            if (!is_array($package) || ($package['name'] ?? null) !== 'VioletBlue' || ($package['layout'] ?? null) !== 'wwwroot' || support_version::normalize($package['version'] ?? null) !== $version) throw new RuntimeException('更新包版本标记与 Release 不一致，请使用打包脚本生成发布包');
            foreach (['index.php', 'api.php', 'app/bootstrap.php', 'app/src/bootstrap.php', 'app/src/version.yaml', 'build/.vite/manifest.json'] as $required) {
                if ($zip->locateName($required) === false) throw new RuntimeException('更新包缺少程序文件：' . $required);
            }
            $files = []; $seen = []; $total = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (!is_array($stat)) throw new RuntimeException('无法读取更新包目录');
                $path = $stat['name'];
                self::target($directory, rtrim($path, '/'));
                $attributes = 0; $operatingSystem = 0;
                if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes) && (($attributes >> 16) & 0170000) === 0120000) throw new RuntimeException('更新包不能包含符号链接');
                if (($stat['encryption_method'] ?? 0) !== 0) throw new RuntimeException('更新包不能加密');
                $key = strtolower(rtrim($path, '/'));
                if (isset($seen[$key])) throw new RuntimeException('更新包包含重复文件路径');
                $seen[$key] = true;
                if (str_ends_with($path, '/')) continue;
                $total += $stat['size'];
                if ($total > 1073741824 || $stat['size'] > 134217728) throw new RuntimeException('更新包展开大小超过限制');
                if (self::protectedPath($path) || !self::allowed($path)) continue;
                $target = self::target($directory, $path);
                if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true) && !is_dir(dirname($target))) throw new RuntimeException('无法创建更新暂存目录');
                $input = $zip->getStream($path); $output = fopen($target, 'xb');
                if ($input === false || $output === false) throw new RuntimeException('无法暂存更新文件');
                try { if (stream_copy_to_stream($input, $output, $stat['size'] + 1) !== $stat['size']) throw new RuntimeException('更新文件内容不完整'); }
                finally { fclose($input); fclose($output); }
                $files[] = $path;
            }
            $versionData = \Symfony\Component\Yaml\Yaml::parseFile($directory . '/app/src/version.yaml');
            if (!is_array($versionData) || support_version::normalize($versionData['version'] ?? null) !== $version) throw new RuntimeException('程序版本文件与 Release 不一致');
            $manifest = json_decode((string) file_get_contents($directory . '/build/.vite/manifest.json'), true);
            if (!is_array($manifest) || !is_string($manifest['index.html']['file'] ?? null)) throw new RuntimeException('更新包前端 manifest 无效');
            foreach ($manifest as $chunk) {
                if (!is_array($chunk)) throw new RuntimeException('更新包前端 manifest 无效');
                foreach (array_filter([$chunk['file'] ?? null, ...($chunk['css'] ?? [])]) as $asset) {
                    if (!is_string($asset) || !is_file(self::target($directory, 'build/' . $asset))) throw new RuntimeException('更新包缺少前端分片');
                }
            }
            if (isset($package['files'])) {
                if (!is_array($package['files']) || !array_is_list($package['files'])) throw new RuntimeException('程序文件清单无效');
                $declared = $package['files']; $actual = $files;
                foreach ($declared as $path) if (!is_string($path)) throw new RuntimeException('程序文件清单无效');
                sort($declared); sort($actual);
                if ($declared !== $actual) throw new RuntimeException('更新包程序文件与清单不一致');
            }
            // 入口 manifest 和版本标记最后替换，保留旧哈希资源供已打开页面使用。
            usort($files, static fn(string $a, string $b): int => self::priority($a) <=> self::priority($b));
            return $files;
        } finally { $zip->close(); }
    }

    /** 用途：安排更新提交顺序；入参：相对路径；返回值：提交优先级。 */
    private static function priority(string $path): int
    {
        return match ($path) { '.package.json' => 3, 'app/src/version.yaml' => 2, 'build/.vite/manifest.json' => 1, default => 0 };
    }
}
