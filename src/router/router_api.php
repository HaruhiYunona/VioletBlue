<?php
declare(strict_types=1);
namespace VioletBlue\router;

use VioletBlue\controller\controller_api;
use VioletBlue\service\service_auth;
use VioletBlue\support\{support_input, support_config, support_api_error};

/** 校验接口、请求方法和会话权限，统一输出 JSON 响应。 */
final class router_api
{
    public const READ = ['getTagList', 'getSongList', 'getNotice', 'search', 'random', 'detail', 'vUpInfo', 'siteInfo', 'getVersion', 'getUpdate', 'themeInfo', 'sessionInfo'];
    public const WRITE = ['submitSong', 'submitSongs', 'delete', 'edit', 'clearSongList', 'startImport', 'importStatus', 'cancelImport', 'changeOption', 'changeSetting', 'uploadFile', 'changeTheme', 'logout', 'startUpdate', 'getUpdateStatus'];

    /**
     * 执行接口校验、鉴权及异常到 HTTP 状态的转换。
     * @param string $action 接口操作名
     * @return void 输出 JSON 响应
     */
    public static function run(string $action): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $read = in_array($action, self::READ, true);
        if (!$read && !in_array($action, self::WRITE, true) && $action !== 'passport') {
            self::respond('接口不存在', 0, 404);
            return;
        }
        if (!in_array($method, $read ? ['GET', 'POST'] : ['POST'], true)) {
            header('Allow: ' . ($read ? 'GET, POST' : 'POST'));
            self::respond('请求方法不支持', 0, 405);
            return;
        }
        if (!support_config::installed()) {
            self::respond('请先完成安装', 0, 503, support_api_error::NotInstalled);
            return;
        }
        if (!$read && !service_auth::csrfValid()) {
            self::respond('页面验证已过期，请刷新页面后重试', 0, 403, support_api_error::CsrfInvalid);
            return;
        }
        if ((!$read || $action === 'getUpdate') && $action !== 'passport' && !service_auth::loggedIn()) {
            self::respond('登录状态已失效，请重新登录', 0, 401, support_api_error::AuthRequired);
            return;
        }
        if (($read || in_array($action, ['startImport', 'importStatus', 'cancelImport', 'startUpdate', 'getUpdateStatus'], true)) && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            $data = (new controller_api())->dispatch($action, support_input::request());
            if ($action === 'passport' && $data === false) {
                self::respond('管理员密码错误，请重新输入', 0, 401, support_api_error::InvalidCredentials);
                return;
            }
            self::respond($data, $data === false ? 0 : 1);
            if ($action === 'startImport' && function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                try { (new \VioletBlue\service\service_import())->process($data['id']); }
                catch (\Throwable $error) { error_log('后台导入执行中断：' . $error->getMessage()); }
            }
            if ($action === 'startUpdate' && function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                try { (new \VioletBlue\service\service_update())->process($data['id']); }
                catch (\Throwable $error) { error_log('后台更新执行中断：' . $error->getMessage()); }
            }
        } catch (\InvalidArgumentException $error) {
            self::respond($error->getMessage(), 0, 422);
        } catch (\OutOfBoundsException $error) {
            self::respond($error->getMessage(), 0, 404);
        } catch (\DomainException $error) {
            self::respond($error->getMessage(), 0, 409);
        } catch (\PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                self::respond('歌曲已存在', 0, 409);
            } else {
                error_log((string) $error);
                self::respond('数据库暂时不可用，请稍后重试', 0, 503, support_api_error::DatabaseUnavailable);
            }
        } catch (\Throwable $error) {
            error_log((string) $error);
            $status = in_array($error->getCode(), [502, 503], true) ? $error->getCode() : 500;
            self::respond($status === 500 ? '服务暂时不可用，请检查服务器日志' : $error->getMessage(), 0, $status);
        }
    }

    /**
     * 输出统一接口响应。
     * @param mixed $content 响应内容
     * @param int $code 业务状态码，1 为成功
     * @param int $status HTTP 状态码
     * @param ?support_api_error $error 明确指定的错误类型，省略时按状态推断
     * @return void 无
     */
    public static function respond(mixed $content, int $code = 1, int $status = 200, ?support_api_error $error = null): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // 随机结果、登录、写操作不能复用旧响应；页面更新也应立即可见。
        header('Cache-Control: no-store');
        $response = ['code' => $code, 'content' => $content];
        if ($code !== 1) {
            $response['error'] = ($error ?? support_api_error::fromStatus($status))->value;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
