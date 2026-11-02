<?php
declare(strict_types=1);
namespace VioletBlue\support;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/** 解析 YAML 配置，管理源码与安装包路径及原子写入。 */
final class support_config
{
    /**
     * 取得 PHP 应用根目录。
     * @return string 应用根路径
     */
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * 解析源码或 wwwroot 安装包的公开目录。
     * @return string 静态资源和入口所在路径
     */
    public static function publicRoot(): string
    {
        return defined('VB_PUBLIC_ROOT') ? (string) constant('VB_PUBLIC_ROOT') : self::root() . '/public';
    }

    /**
     * 判断是否使用带 PHP 保护首行的配置。
     * @return bool 是否启用安装包配置格式
     */
    public static function protectedConfig(): bool
    {
        return defined('VB_PROTECTED_CONFIG') && constant('VB_PROTECTED_CONFIG') === true;
    }

    /**
     * 生成指定 YAML 配置的完整路径。
     * @param string $name 仅含小写字母的配置名
     * @return string 配置文件路径
     */
    public static function path(string $name): string
    {
        if (!preg_match('/\A[a-z]+\z/D', $name)) {
            throw new RuntimeException('无效配置名称');
        }
        return self::root() . '/src/config/config_' . $name . '.yaml' . (self::protectedConfig() ? '.php' : '');
    }

    /**
     * 取得当前站点独立的会话目录。
     * @return string 会话存储路径
     */
    public static function sessionDirectory(): string
    {
        return self::protectedConfig()
            ? sys_get_temp_dir() . '/violetblue-sessions-' . substr(hash('sha256', self::root()), 0, 20)
            : self::root() . '/storage/sessions';
    }

    /**
     * 生成兼容 YAML 的 PHP 访问保护首行。
     * @return string 配置保护首行
     */
    public static function configGuard(): string
    {
        return "#<?php if (!headers_sent()) { http_response_code(404); } exit; __halt_compiler();\n";
    }

    /**
     * 解析指定 YAML 配置。
     * @param string $name 配置名
     * @return array 配置键值映射
     */
    public static function read(string $name): array
    {
        $path = self::path($name);
        if (!is_file($path)) {
            throw new RuntimeException('配置文件不存在：' . $name);
        }
        $data = Yaml::parseFile($path, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        if (!is_array($data)) {
            throw new RuntimeException('配置必须为 YAML 映射');
        }
        return $data;
    }

    /**
     * 序列化 YAML 并原子替换配置文件。
     * @param string $name 配置名
     * @param array $data 要保存的键值映射
     * @return void 无
     */
    public static function write(string $name, array $data): void
    {
        self::atomic(self::path($name), (self::protectedConfig() ? self::configGuard() : '') . Yaml::dump($data, 2, 2));
    }

    /**
     * 通过同目录临时文件和重命名写入内容。
     * @param string $path 目标文件路径
     * @param string $content 完整文件内容
     * @return void 无
     */
    public static function atomic(string $path, string $content): void
    {
        $temp = tempnam(dirname($path), '.vb-');
        if ($temp === false) {
            throw new RuntimeException('无法创建临时文件');
        }
        try {
            if (file_put_contents($temp, $content, LOCK_EX) !== strlen($content) || !rename($temp, $path)) {
                throw new RuntimeException('文件保存失败');
            }
        } finally {
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    /**
     * 检查安装完成标记。
     * @return bool 是否已经安装
     */
    public static function installed(): bool
    {
        return is_file(self::publicRoot() . '/install/.block');
    }
}
