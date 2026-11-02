import { isRecord } from '../types/type_api'

/** 后端业务错误及浏览器请求错误的统一分类。 */
export enum ErrorType {
  InvalidCredentials = 'INVALID_CREDENTIALS',
  AuthRequired = 'AUTH_REQUIRED',
  CsrfInvalid = 'CSRF_INVALID',
  Forbidden = 'FORBIDDEN',
  NotInstalled = 'NOT_INSTALLED',
  ValidationFailed = 'VALIDATION_FAILED',
  NotFound = 'NOT_FOUND',
  MethodNotAllowed = 'METHOD_NOT_ALLOWED',
  Conflict = 'CONFLICT',
  PayloadTooLarge = 'PAYLOAD_TOO_LARGE',
  RateLimited = 'RATE_LIMITED',
  DatabaseUnavailable = 'DATABASE_UNAVAILABLE',
  ServiceUnavailable = 'SERVICE_UNAVAILABLE',
  ServerError = 'SERVER_ERROR',
  UpstreamError = 'UPSTREAM_ERROR',
  Timeout = 'TIMEOUT',
  RequestFailed = 'REQUEST_FAILED',
  Network = 'NETWORK_ERROR',
  Cancelled = 'CANCELLED',
  InvalidResponse = 'INVALID_RESPONSE',
}

const messages: Record<ErrorType, string> = {
  [ErrorType.InvalidCredentials]: '管理员密码错误，请重新输入',
  [ErrorType.AuthRequired]: '登录状态已失效，请重新登录',
  [ErrorType.CsrfInvalid]: '页面验证已过期，请刷新页面后重试',
  [ErrorType.Forbidden]: '没有执行此操作的权限',
  [ErrorType.NotInstalled]: '请先完成网站安装',
  [ErrorType.ValidationFailed]: '提交内容不符合要求，请检查后重试',
  [ErrorType.NotFound]: '请求的内容不存在或已被删除',
  [ErrorType.MethodNotAllowed]: '请求方式不受支持，请刷新页面后重试',
  [ErrorType.Conflict]: '数据已存在或与当前状态冲突，请检查后重试',
  [ErrorType.PayloadTooLarge]: '提交的数据过大，请调整服务器的请求体限制后重试',
  [ErrorType.RateLimited]: '请求过于频繁，请稍后再试',
  [ErrorType.DatabaseUnavailable]: '数据库暂时不可用，请稍后重试',
  [ErrorType.ServiceUnavailable]: '服务暂时不可用，请稍后重试',
  [ErrorType.ServerError]: '服务器处理失败，请稍后重试',
  [ErrorType.UpstreamError]: '上游服务暂时不可用，请稍后重试',
  [ErrorType.Timeout]: '请求超时，请稍后重试',
  [ErrorType.RequestFailed]: '操作失败，请稍后重试',
  [ErrorType.Network]: '网络连接失败，请检查网络后重试',
  [ErrorType.Cancelled]: '操作已取消',
  [ErrorType.InvalidResponse]: '服务器返回的数据格式不正确，请刷新页面后重试',
}

/** 带错误分类和 HTTP 状态的请求异常。 */
export class ApiError extends Error {
  /** 用途：创建可展示的请求异常；入参：错误类型、HTTP 状态、具体说明；返回值：异常实例。 */
  constructor(readonly type: ErrorType, readonly status = 0, detail?: string) {
    super(detail?.trim() || messages[type])
    this.name = 'ApiError'
  }
}

/** 将接口失败、HTTP 错误和传输异常转换为统一提示。 */
export class ErrorMapper {
  /** 用途：从 HTTP 状态推断分类；入参：HTTP 状态、操作名；返回值：错误类型。 */
  static fromStatus(status: number, action = ''): ErrorType {
    switch (status) {
      case 0: return ErrorType.Network
      case 400: case 415: case 422: return ErrorType.ValidationFailed
      case 401: return action === 'passport' ? ErrorType.InvalidCredentials : ErrorType.AuthRequired
      case 403: return ErrorType.Forbidden
      case 404: return ErrorType.NotFound
      case 405: return ErrorType.MethodNotAllowed
      case 408: case 504: return ErrorType.Timeout
      case 409: return ErrorType.Conflict
      case 413: return ErrorType.PayloadTooLarge
      case 429: return ErrorType.RateLimited
      case 502: return ErrorType.UpstreamError
      case 503: return ErrorType.ServiceUnavailable
      default: return status >= 500 ? ErrorType.ServerError : ErrorType.RequestFailed
    }
  }

  /** 用途：转换失败响应，优先使用后端枚举并保留具体校验原因；入参：状态、JSON、操作名；返回值：请求异常。 */
  static fromResponse(status: number, result: unknown, action = ''): ApiError {
    const failure = isRecord(result) && result.code === 0 ? result : undefined
    const declared = Object.values(ErrorType).find((value) => value === failure?.error)
    const type = declared ?? this.fromStatus(status, action)
    const allowDetail = !declared || [ErrorType.ValidationFailed, ErrorType.NotFound, ErrorType.Conflict, ErrorType.UpstreamError, ErrorType.ServiceUnavailable, ErrorType.RequestFailed].includes(type)
    const detail = allowDetail && failure && typeof failure.content === 'string' ? failure.content : undefined
    return new ApiError(type, status, detail)
  }

  /** 用途：转换网络、超时和取消异常；入参：底层异常；返回值：请求异常。 */
  static fromTransport(error: unknown): ApiError {
    if (error instanceof ApiError) return error
    if (error instanceof Error && error.name === 'AbortError') return new ApiError(ErrorType.Cancelled)
    if (error instanceof Error && error.name === 'TimeoutError') return new ApiError(ErrorType.Timeout)
    return new ApiError(ErrorType.Network)
  }

  /** 用途：读取统一提示或本地校验说明；入参：未知异常；返回值：提示文字。 */
  static message(error: unknown): string {
    if (error instanceof ApiError) return error.message
    if (error instanceof Error && ['AbortError', 'TimeoutError', 'TypeError'].includes(error.name)) return this.fromTransport(error).message
    return error instanceof Error && error.message.trim() ? error.message : messages[ErrorType.RequestFailed]
  }
}

/** 用途：识别主动取消请求；入参：未知异常；返回值：是否取消。 */
export function isCancellation(error: unknown): boolean {
  return error instanceof ApiError ? error.type === ErrorType.Cancelled : error instanceof Error && error.name === 'AbortError'
}

/** 用途：供页面读取错误提示；入参：未知异常；返回值：提示文字。 */
export function messageOf(error: unknown): string { return ErrorMapper.message(error) }
