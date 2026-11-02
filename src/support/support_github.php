<?php
declare(strict_types=1);
namespace VioletBlue\support;

use Closure;
use RuntimeException;

/** 请求 GitHub Release 元数据及附件，限制下载来源、大小和耗时。 */
final class support_github
{
    public const MAX_BYTES = 268435456;

    /** 用途：注入离线测试传输器；入参：可选传输回调；返回值：客户端。 */
    public function __construct(private readonly ?Closure $transport = null) {}

    /** 用途：限制 HTTPS 请求及重定向来源；入参：URL；返回值：无，无效时抛异常。 */
    private function validateUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null || !in_array(parse_url($url, PHP_URL_PORT), [null, 443], true)
            || !in_array($host, ['api.github.com', 'github.com', 'release-assets.githubusercontent.com', 'objects.githubusercontent.com', 'github-releases.githubusercontent.com'], true)) {
            throw new RuntimeException('更新下载地址不属于 GitHub');
        }
    }

    /** 用途：请求 JSON 或下载附件；入参：URL、可选目标文件、进度回调；返回值：JSON 或空数组。 */
    public function request(string $url, ?string $destination = null, ?Closure $progress = null): array
    {
        $this->validateUrl($url);
        if ($this->transport !== null) return ($this->transport)($url, $destination, $progress);
        $file = $destination === null ? null : fopen($destination, 'wb');
        if ($destination !== null && $file === false) throw new RuntimeException('无法保存更新包');
        try {
            for ($redirect = 0; $redirect < 6; $redirect++) {
                $this->validateUrl($url);
                $body = ''; $location = ''; $size = 0;
                $handle = curl_init($url);
                if ($handle === false) throw new RuntimeException('无法创建 GitHub 请求');
                $headers = ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2026-03-10'];
                $token = getenv('VB_GITHUB_TOKEN');
                if (parse_url($url, PHP_URL_HOST) === 'api.github.com' && is_string($token) && $token !== '') $headers[] = 'Authorization: Bearer ' . $token;
                $options = [CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => $destination === null ? 20 : 180,
                    CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_USERAGENT => 'VioletBlue-Updater', CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_HEADERFUNCTION => static function (\CurlHandle $curl, string $line) use (&$location): int {
                        if (stripos($line, 'Location:') === 0) $location = trim(substr($line, 9));
                        return strlen($line);
                    },
                    CURLOPT_WRITEFUNCTION => static function (\CurlHandle $curl, string $chunk) use (&$body, &$size, $file, $destination, $progress): int {
                        $size += strlen($chunk);
                        if ($size > ($destination === null ? 8 * 1024 * 1024 : self::MAX_BYTES)) return 0;
                        if (is_resource($file)) {
                            if (fwrite($file, $chunk) !== strlen($chunk)) return 0;
                            if ($progress !== null) $progress($size);
                        } else $body .= $chunk;
                        return strlen($chunk);
                    }];
                if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
                curl_setopt_array($handle, $options);
                $success = curl_exec($handle);
                $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                if (in_array($status, [301, 302, 303, 307, 308], true) && $location !== '') {
                    if (is_resource($file)) { ftruncate($file, 0); rewind($file); }
                    $url = str_starts_with($location, '/') ? 'https://' . parse_url($url, PHP_URL_HOST) . $location : $location;
                    continue;
                }
                if ($success === false) throw new RuntimeException('GitHub 连接或下载失败，请检查服务器网络、证书和可用磁盘空间', 502);
                if ($status === 403 || $status === 429) throw new RuntimeException('GitHub 请求被拒绝或达到限额，请稍后重试；可配置 VB_GITHUB_TOKEN', 503);
                if ($status !== 200) throw new RuntimeException('GitHub 返回 HTTP ' . $status . '，请检查仓库及 Release 是否公开', 502);
                if ($destination !== null) return [];
                $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
                if (!is_array($data)) throw new RuntimeException('GitHub 返回格式无效', 502);
                return $data;
            }
            throw new RuntimeException('GitHub 下载重定向次数过多', 502);
        } finally { if (is_resource($file)) fclose($file); }
    }
}
