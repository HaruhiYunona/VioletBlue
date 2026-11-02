<?php
declare(strict_types=1);
namespace VioletBlue\service;

use VioletBlue\model\model_playlist;
use VioletBlue\support\support_config;

/** 管理管理员会话、密码验证和 CSRF 令牌。 */
final class service_auth
{
    /**
     * 启动会话并创建 CSRF 令牌。
     * @return void 无
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $directory = support_config::sessionDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        session_name('VB_SESSION');
        session_save_path($directory);
        session_start([
            'use_strict_mode' => 1, 'use_only_cookies' => 1,
            'cookie_httponly' => 1, 'cookie_secure' => self::secure(),
            'cookie_samesite' => 'Strict', 'cookie_path' => '/', 'gc_maxlifetime' => 7200,
        ]);
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        self::cookie('XSRF-TOKEN', $_SESSION['csrf']);
    }

    /**
     * 判断当前请求是否使用 HTTPS。
     * @return bool 是否启用安全 Cookie
     */
    private static function secure(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    /**
     * 设置前端可读的同源 Cookie。
     * @param string $name Cookie 名
     * @param string $value Cookie 值
     * @return void 无
     */
    private static function cookie(string $name, string $value): void
    {
        setcookie($name, $value, ['path' => '/', 'secure' => self::secure(), 'httponly' => false, 'samesite' => 'Strict']);
    }

    /**
     * 比较请求令牌与会话内的 CSRF 令牌。
     * @return bool 令牌是否匹配
     */
    public static function csrfValid(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '';
        return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

    /**
     * 检查登录有效期及当前密码凭据。
     * @return bool 会话是否已认证
     */
    public static function loggedIn(): bool
    {
        if (!isset($_SESSION['authenticated'], $_SESSION['expires'], $_SESSION['credential']) || $_SESSION['expires'] < time()) {
            return false;
        }
        $hash = (new model_playlist())->option('passWd');
        // 密码摘要变化后，旧会话内的凭据摘要不再匹配。
        return hash_equals($_SESSION['credential'], hash('sha256', $hash));
    }

    /**
     * 验证密码并创建管理会话，成功时升级旧密码摘要。
     * @param string $password 管理员输入的明文密码
     * @return bool 登录是否成功
     */
    public static function login(string $password): bool
    {
        if ($password === '' || strlen($password) > 1024) {
            return false;
        }
        $model = new model_playlist();
        $hash = $model->option('passWd');
        $legacy = preg_match('/\A[a-f0-9]{32}\z/D', $hash) === 1;
        $valid = $legacy ? hash_equals($hash, md5($password)) : password_verify($password, $hash);
        if (!$valid) {
            return false;
        }
        if ($legacy || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $model->setOption('passWd', $hash);
        }
        // 登录成功后轮换会话编号，再绑定当前密码摘要。
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['credential'] = hash('sha256', $hash);
        $_SESSION['expires'] = time() + 7200;
        // 此 Cookie 仅为原页面提供编辑入口提示，不作为认证凭据。
        self::cookie('passWd', 'logged-in');
        return true;
    }

    /**
     * 清除登录状态及前端编辑入口标记。
     * @return void 无
     */
    public static function invalidate(): void
    {
        unset($_SESSION['authenticated'], $_SESSION['credential'], $_SESSION['expires']);
        self::cookie('passWd', '');
    }

    /**
     * 清除登录状态并轮换会话编号。
     * @return bool 是否成功
     */
    public static function logout(): bool
    {
        self::invalidate();
        session_regenerate_id(true);
        return true;
    }
}
