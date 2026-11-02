<?php
declare(strict_types=1);
namespace VioletBlue\service;

use Closure;
use RuntimeException;
use VioletBlue\support\support_config;

/** 获取主播资料和更新信息，管理缓存、备用源与退避。 */
final class service_remote
{
    private int $retryDelay = 300;

    /**
     * 配置上游请求和缓存目录。
     * @param ?Closure $transport 可选的测试请求回调
     * @param ?string $cacheDirectory 可选的资料缓存目录
     */
    public function __construct(private readonly ?Closure $transport = null, private readonly ?string $cacheDirectory = null) {}

    /**
     * 请求 HTTPS 上游并限制连接时间、响应体和重试间隔。
     * @param string $url 固定上游接口地址
     * @return array 解码后的 JSON 数据
     */
    private function get(string $url): array
    {
        if ($this->transport !== null) {
            return ($this->transport)($url);
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('无法初始化 HTTP 客户端');
        }
        $body = '';
        $retry = '';
        $options = [
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'VioletBlue/1.5',
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Referer: https://www.bilibili.com/'],
            CURLOPT_HEADERFUNCTION => static function (\CurlHandle $curl, string $line) use (&$retry): int {
                if (stripos($line, 'Retry-After:') === 0) {
                    $retry = trim(substr($line, 12));
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function (\CurlHandle $curl, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 2 * 1024 * 1024) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ];
        // Windows OpenSSL 构建可使用系统 CA，始终保持证书与主机名验证。
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        curl_setopt_array($handle, $options);
        $success = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($status === 429) {
            $seconds = ctype_digit($retry) ? (int) $retry : max(0, (strtotime($retry) ?: time()) - time());
            $this->retryDelay = max(300, min(86400, $seconds));
        }
        if ($success === false || $status !== 200) {
            throw new RuntimeException('上游服务暂不可用，HTTP ' . $status, $status);
        }
        $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('上游返回内容无效');
        }
        return $data;
    }

    /**
     * 校验主播标识、数字字段及头像地址。
     * @param array $data 上游资料
     * @param int $mid 期望的主播 MID
     * @return array 统一的主播资料
     */
    private function normalize(array $data, int $mid): array
    {
        $result = [];
        foreach (['mid', 'level', 'follower', 'following'] as $field) {
            $value = $data[$field] ?? null;
            if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
                throw new RuntimeException('上游资料数字字段无效');
            }
            $result[$field] = (int) $value;
        }
        if ($result['mid'] !== $mid) {
            throw new RuntimeException('上游返回的 MID 不匹配');
        }
        foreach (['name', 'sign', 'face'] as $field) {
            $value = $data[$field] ?? null;
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || strlen($value) > 10000) {
                throw new RuntimeException('上游资料文本字段无效');
            }
            $result[$field] = $value;
        }
        if ($result['name'] === '') {
            throw new RuntimeException('上游昵称为空');
        }
        $face = str_starts_with($result['face'], 'http://') ? 'https://' . substr($result['face'], 7) : $result['face'];
        if (filter_var($face, FILTER_VALIDATE_URL) === false || parse_url($face, PHP_URL_SCHEME) !== 'https' || parse_url($face, PHP_URL_USER) !== null) {
            throw new RuntimeException('上游头像地址无效');
        }
        $result['face'] = $face;
        return $result;
    }

    /**
     * 读取 JSON 缓存。
     * @param string $path 缓存文件路径
     * @return ?array 缓存映射，无效或不存在时为 null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true, 32);
        return is_array($data) ? $data : null;
    }

    /**
     * 按缓存和退避状态依次查询第三方及官方资料。
     * @return array 主播资料，失败时使用缓存或本地配置
     */
    public function profile(): array
    {
        $site = support_config::read('site');
        $mid = (int) $site['mid'];
        $fallback = [
            'code' => 1, 'time' => time(), 'mid' => $mid, 'name' => $site['nickname'],
            'sign' => $site['sign'], 'face' => '/static/img/face.png?v=' . filemtime(support_config::publicRoot() . '/static/img/face.png'),
            'level' => $site['level'], 'follower' => $site['follower'], 'following' => $site['following'],
        ];
        if ($mid === 0) {
            return $fallback;
        }
        $directory = $this->cacheDirectory ?? support_config::root() . '/storage';
        $path = $directory . '/profile_' . $mid . '.json';
        $retryPath = $directory . '/profile_retry_' . $mid . '.json';
        $uapiRetryPath = $directory . '/profile_uapi_retry_' . $mid . '.json';
        $rawCache = $this->readJson($path);
        $cached = null;
        if ($rawCache !== null) {
            try {
                $cached = ['code' => 1, 'time' => (int) ($rawCache['time'] ?? 0), ...$this->normalize($rawCache, $mid)];
            } catch (\Throwable) {
                $cached = null;
            }
        }
        $cacheSeconds = min(86400, max(0, (int) ($site['api_cache_time'] ?? 3600)));
        if ($cached !== null && $cached['time'] > time() - $cacheSeconds) {
            return $cached;
        }
        $retry = $this->readJson($retryPath);
        if (is_int($retry['retry_after'] ?? null) && $retry['retry_after'] > time()) {
            return $cached ?? $fallback;
        }

        // 非阻塞文件锁：并发访问只让一个请求更新，其余立即使用缓存或本地资料。
        $lock = fopen($directory . '/profile_' . $mid . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            return $cached ?? $fallback;
        }
        try {
            foreach (['uapi', 'bilibili'] as $source) {
                $sourceRetry = $source === 'uapi' ? $this->readJson($uapiRetryPath) : null;
                if (is_int($sourceRetry['retry_after'] ?? null) && $sourceRetry['retry_after'] > time()) {
                    continue;
                }
                try {
                    if ($source === 'uapi') {
                        // 沿用原仓库 PR #5 已合并的服务，直接请求 HTTPS，响应字段在顶层。
                        $profile = $this->get('https://uapis.cn/api/v1/social/bilibili/userinfo?uid=' . $mid);
                    } else {
                        $response = $this->get('https://api.bilibili.com/x/web-interface/card?mid=' . $mid);
                        $data = $response['data'] ?? null;
                        $card = is_array($data) ? ($data['card'] ?? null) : null;
                        if (($response['code'] ?? null) !== 0 || !is_array($card)) {
                            throw new RuntimeException('B站卡片资料暂不可用');
                        }
                        $profile = [
                            'mid' => $card['mid'] ?? null, 'name' => $card['name'] ?? null, 'sign' => $card['sign'] ?? null,
                            'face' => $card['face'] ?? null, 'level' => $card['level_info']['current_level'] ?? null,
                            'follower' => $data['follower'] ?? $card['fans'] ?? null, 'following' => $card['attention'] ?? null,
                        ];
                    }
                    $result = ['code' => 1, 'time' => time(), ...$this->normalize($profile, $mid)];
                    support_config::atomic($path, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    return $result;
                } catch (\Throwable $error) {
                    error_log('主播资料更新失败 [' . $source . ']：' . $error->getMessage());
                    if ($source === 'uapi' && $error->getCode() === 429) {
                        support_config::atomic($uapiRetryPath, json_encode(['retry_after' => time() + $this->retryDelay], JSON_THROW_ON_ERROR));
                    }
                }
            }
            support_config::atomic($retryPath, json_encode(['retry_after' => time() + $this->retryDelay], JSON_THROW_ON_ERROR));
            return $cached ?? $fallback;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

}
