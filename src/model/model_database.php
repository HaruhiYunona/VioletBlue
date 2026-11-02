<?php
declare(strict_types=1);
namespace VioletBlue\model;

use PDO;
use InvalidArgumentException;
use VioletBlue\support\support_config;

/** 管理 PDO 连接、表前缀及允许访问的表名。 */
final class model_database
{
    private static ?PDO $connection = null;
    private static ?string $prefix = null;

    /**
     * 创建原生预处理的 MySQL 连接。
     * @param array $config host、port、database、username、password 配置
     * @return PDO PDO 连接
     */
    public static function connect(array $config): PDO
    {
        foreach (['host', 'database', 'username', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || str_contains($config[$key], ';')) {
                // 密码和用户名不参与 DSN，允许分号。
                if (in_array($key, ['host', 'database'], true) || !isset($config[$key]) || !is_string($config[$key])) {
                    throw new InvalidArgumentException('数据库配置不正确：' . $key);
                }
            }
        }
        $port = \VioletBlue\support\support_input::integer($config['port'] ?? 3306, 1, 65535);
        return new PDO('mysql:host=' . $config['host'] . ';port=' . $port . ';dbname=' . $config['database'] . ';charset=utf8mb4', $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            \Pdo\Mysql::ATTR_MULTI_STATEMENTS => false,
        ]);
    }

    /**
     * 读取数据库配置并复用当前请求的连接。
     * @return PDO PDO 连接
     */
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = support_config::read('database');
            self::$prefix = self::validatePrefix($config['prefix'] ?? '');
            self::$connection = self::connect($config);
        }
        return self::$connection;
    }

    /**
     * 校验表前缀字符与长度。
     * @param mixed $prefix 待校验的表前缀
     * @return string 合法表前缀
     */
    public static function validatePrefix(mixed $prefix): string
    {
        if (!is_string($prefix) || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,39}\z/D', $prefix)) {
            throw new InvalidArgumentException('表前缀必须以字母开头，仅含字母、数字及下划线，最长40位');
        }
        return $prefix;
    }

    /**
     * 生成白名单内带前缀的 SQL 表名。
     * @param string $name song 或 others
     * @return string 带反引号的表名
     */
    public static function table(string $name): string
    {
        self::connection();
        if (!in_array($name, ['song', 'others'], true)) {
            throw new InvalidArgumentException('无效表名');
        }
        return '`' . self::$prefix . '_' . $name . '`';
    }

    /**
     * 注入测试使用的独立数据库连接。
     * @param PDO $connection 测试 PDO 连接
     * @param string $prefix 测试表前缀
     * @return void 无
     */
    public static function useConnection(PDO $connection, string $prefix): void
    {
        self::$prefix = self::validatePrefix($prefix);
        self::$connection = $connection;
    }
}
