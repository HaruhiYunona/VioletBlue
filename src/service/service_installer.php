<?php
declare(strict_types=1);
namespace VioletBlue\service;

use InvalidArgumentException;
use PDOException;
use RuntimeException;
use VioletBlue\model\model_database;
use VioletBlue\support\{support_config, support_input};

/** 创建站点数据表、初始账号及安装配置。 */
final class service_installer
{
    private string $databaseStage = '连接数据库';

    /**
     * 将数据库异常转换为安装阶段和错误码提示。
     * @param PDOException $error 数据库异常
     * @return string 可显示的安装错误信息
     */
    public function databaseError(PDOException $error): string
    {
        $state = $error->errorInfo[0] ?? (string) $error->getCode();
        $state = is_string($state) && preg_match('/\A[A-Z0-9]{5}\z/D', $state) ? $state : '未知';
        $code = $error->errorInfo[1] ?? 0;
        $code = is_int($code) ? $code : 0;
        $reason = match ($code) {
            1044 => '账号无法访问所填数据库，请检查该库的授权。',
            1045, 1698 => '数据库拒绝登录，请检查账号、密码及连接来源授权。',
            1049 => '所填数据库不存在，请先创建数据库。',
            2002, 2003 => '无法连接数据库服务，请检查地址、端口及PHP进程的网络或socket访问权限。',
            2005 => '无法解析数据库地址，请检查主机名。',
            2026 => '数据库TLS连接失败，请检查数据库证书及TLS配置。',
            2054, 2059, 2061 => '数据库认证方式与当前PHP客户端不匹配，请检查认证插件及安全连接配置。',
            1142, 1143, 1227 => '数据库账号缺少当前操作权限，请检查建表、查询和写入权限。',
            1071, 1709 => '数据库索引长度限制不足，当前建表语句超过了InnoDB允许的索引长度；请提供数据库版本和错误码进一步处理。',
            1064 => '数据库不接受当前SQL语句，请提供数据库版本及PHP错误日志中的对应异常。',
            1050 => '目标表已经存在，安装程序不会覆盖；保留旧数据请使用迁移工具。',
            1115, 1273 => '数据库不支持所需的utf8mb4字符集或排序规则。',
            1286, 3161 => '数据库无法使用InnoDB存储引擎，请检查引擎配置。',
            2006, 2013 => '数据库连接已断开，请检查数据库服务状态及超时设置。',
            default => '请查看PHP错误日志中同一时间的SQLSTATE异常，确认数据库返回的具体原因。',
        };
        return $this->databaseStage . '失败（SQLSTATE ' . $state . '，MySQL ' . $code . '）。' . $reason;
    }

    /**
     * 在空表前缀下创建站点并保存安装配置。
     * @param array $input 数据库连接、表前缀及 admin_password 表单字段
     * @return void 无
     */
    public function install(array $input): void
    {
        if (support_config::installed()) {
            throw new RuntimeException('站点已经安装，禁止重复安装');
        }
        $config = [
            'host' => trim(support_input::text($input, 'db')),
            'database' => trim(support_input::text($input, 'dbname')),
            'username' => support_input::text($input, 'dbuser'),
            'password' => support_input::text($input, 'dbpassWd'),
            'port' => support_input::integer($input['dbport'] ?? null, 1, 65535),
            'prefix' => model_database::validatePrefix(support_input::text($input, 'tablepre')),
        ];
        $password = support_input::text($input, 'admin_password');
        if (mb_strlen($password) < 6 || strlen($password) > 72) {
            throw new InvalidArgumentException('管理员密码须为6字以上且不超过72字节');
        }
        foreach (['host', 'database', 'username'] as $key) {
            if ($config[$key] === '') {
                throw new InvalidArgumentException('数据库地址、名称和用户名不能为空');
            }
        }
        $lock = fopen(support_config::root() . '/storage/install.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('无法获取安装锁');
        }
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('无法锁定安装程序');
            }
            if (support_config::installed()) {
                throw new RuntimeException('站点已经安装');
            }
            $this->databaseStage = '连接数据库';
            $pdo = model_database::connect($config);
            $prefix = $config['prefix'];
            $this->databaseStage = '检查已有数据表';
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (?, ?)');
            $check->execute([$prefix . '_song', $prefix . '_others']);
            if ((int) $check->fetchColumn() > 0) {
                throw new InvalidArgumentException('已有同名前缀的表，已停止安装以保护数据。迁移旧库请使用 scripts/script_migrate.php');
            }
            $this->databaseStage = '创建公告与账号表';
            $pdo->exec('CREATE TABLE `' . $prefix . '_others` (`name` VARCHAR(200) NOT NULL PRIMARY KEY, `value` TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $this->databaseStage = '创建歌曲表';
            $pdo->exec('CREATE TABLE `' . $prefix . '_song` (
                uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(300) NOT NULL, singer VARCHAR(300) NOT NULL,
                type TEXT NOT NULL, note TEXT NOT NULL, time BIGINT NOT NULL,
                UNIQUE KEY song_identity (name, singer)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $this->databaseStage = '写入初始公告和管理员账号';
            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('INSERT INTO `' . $prefix . '_others` (name,value) VALUES (?,?)');
                foreach (['notice' => '暂无公告喵~', 'type' => '|舰长||流行||古风||粤语||日语||英文|', 'passWd' => password_hash($password, PASSWORD_DEFAULT)] as $name => $value) {
                    $statement->execute([$name, $value]);
                }
                $pdo->commit();
            } catch (\Throwable $error) {
                $pdo->rollBack();
                throw $error;
            }
            support_config::write('database', $config);
            support_config::atomic(support_config::publicRoot() . '/install/.block', 'installed');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
