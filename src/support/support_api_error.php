<?php
declare(strict_types=1);
namespace VioletBlue\support;

/** API 失败响应中的稳定错误类型，与前端错误提示枚举对应。 */
enum support_api_error: string
{
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case AuthRequired = 'AUTH_REQUIRED';
    case CsrfInvalid = 'CSRF_INVALID';
    case Forbidden = 'FORBIDDEN';
    case NotInstalled = 'NOT_INSTALLED';
    case ValidationFailed = 'VALIDATION_FAILED';
    case NotFound = 'NOT_FOUND';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case Conflict = 'CONFLICT';
    case PayloadTooLarge = 'PAYLOAD_TOO_LARGE';
    case RateLimited = 'RATE_LIMITED';
    case DatabaseUnavailable = 'DATABASE_UNAVAILABLE';
    case ServiceUnavailable = 'SERVICE_UNAVAILABLE';
    case ServerError = 'SERVER_ERROR';
    case UpstreamError = 'UPSTREAM_ERROR';
    case Timeout = 'TIMEOUT';
    case RequestFailed = 'REQUEST_FAILED';

    /** 用途：为未指定类型的失败响应分类；入参：HTTP 状态码；返回值：错误类型。 */
    public static function fromStatus(int $status): self
    {
        return match ($status) {
            400, 415, 422 => self::ValidationFailed,
            401 => self::AuthRequired,
            403 => self::Forbidden,
            404 => self::NotFound,
            405 => self::MethodNotAllowed,
            408, 504 => self::Timeout,
            409 => self::Conflict,
            413 => self::PayloadTooLarge,
            429 => self::RateLimited,
            500 => self::ServerError,
            502 => self::UpstreamError,
            503 => self::ServiceUnavailable,
            default => $status >= 500 ? self::ServerError : self::RequestFailed,
        };
    }
}
