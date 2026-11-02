<?php
declare(strict_types=1);
namespace VioletBlue\support;

use Symfony\Component\Yaml\Yaml;

/** 提供单一版本来源及语义版本比较所需的标准形式。 */
final class support_version
{
    /** 用途：规范化正式版编号；入参：版本字符串；返回值：三段版本号，无效时为 null。 */
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/\Av?(\d{1,6})\.(\d{1,6})(?:\.(\d{1,6}))?\z/D', $value, $match)) return null;
        return (int) $match[1] . '.' . (int) $match[2] . '.' . (int) ($match[3] ?? 0);
    }

    /** 用途：读取当前程序版本；入参：无；返回值：程序和资源版本。 */
    public static function info(): array
    {
        $data = Yaml::parseFile(dirname(__DIR__) . '/version.yaml');
        $version = self::normalize(is_array($data) ? ($data['version'] ?? null) : null);
        if ($version === null) throw new \RuntimeException('程序版本文件无效');
        return ['version' => $version, 'res' => (string) ($data['resource'] ?? '')];
    }
}
