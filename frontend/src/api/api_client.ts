/** 同源接口客户端：附加 CSRF 令牌、统一错误提示并管理加载状态。 */
import { isRecord } from '../types/type_api'
import { beginLoading } from '../state/state_loading'
import { ApiError, ErrorMapper, ErrorType } from '../utils/util_error'
export { ApiError, messageOf } from '../utils/util_error'

const operationLabels: Record<string, string> = {
  passport: '正在登录…', logout: '正在退出…', uploadFile: '正在上传文件…',
  submitSong: '正在保存歌曲…', submit: '正在保存歌曲…', submitSongs: '正在导入歌单…', edit: '正在保存歌曲…', delete: '正在删除歌曲…',
  changeOption: '正在保存设置…', changeSetting: '正在保存设置…', changeTheme: '正在保存配色…',
  search: '正在搜索…', random: '正在随机选曲…', detail: '正在加载歌曲…',
  clearSongList: '正在清空歌单…',
  cancelImport: '正在取消导入…',
  startUpdate: '正在创建更新任务…',
}

/** 用途：读取同源 CSRF Cookie；入参：无；返回值：Token。 */
function csrf(): string {
  const value = document.cookie.split('; ').find((entry) => entry.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length)
  return value ? decodeURIComponent(value) : ''
}

/** 用途：处理管理会话失效；入参：HTTP 状态、操作名；返回值：无。 */
function checkSession(status: number, action: string): void {
  if (status === 401 && action !== 'passport') window.dispatchEvent(new Event('vb:unauthorized'))
}

/** 用途：解码网关或 API 响应；入参：响应文本、状态、操作名；返回值：JSON 数据。 */
function decode(text: string, status: number, action: string): unknown {
  try { return JSON.parse(text) }
  catch {
    if (status < 200 || status >= 300) throw ErrorMapper.fromResponse(status, undefined, action)
    throw new ApiError(ErrorType.InvalidResponse, status)
  }
}

/** 用途：统一校验普通与上传响应；入参：JSON、状态、操作名、类型守卫；返回值：业务数据。 */
function validate<T>(result: unknown, status: number, action: string, guard: (value: unknown) => value is T): T {
  if (status < 200 || status >= 300 || (isRecord(result) && result.code === 0)) throw ErrorMapper.fromResponse(status, result, action)
  if (!isRecord(result) || result.code !== 1 || !guard(result.content)) throw new ApiError(ErrorType.InvalidResponse, status)
  return result.content
}

/** 用途：请求接口并用类型守卫校验返回值；入参：操作名、守卫、参数、取消信号；返回值：已验证的数据。 */
export async function request<T>(action: string, guard: (value: unknown) => value is T, body: unknown = {}, signal?: AbortSignal): Promise<T> {
  const endLoading = operationLabels[action] ? beginLoading(operationLabels[action]) : () => {}
  try {
    const payload = JSON.stringify(body)
    let response: Response
    let text: string
    try {
      response = await fetch(`/api.php?action=${encodeURIComponent(action)}`, {
        method: 'POST', credentials: 'same-origin', signal,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf() },
        body: payload,
      })
      checkSession(response.status, action)
      text = await response.text()
    } catch (failure: unknown) { throw ErrorMapper.fromTransport(failure) }
    return validate(decode(text, response.status, action), response.status, action, guard)
  } finally { endLoading() }
}

/**
 * 一次提交完整数据并报告上传进度。
 * @param action 接口操作名
 * @param guard 响应类型守卫
 * @param body 完整请求数据
 * @param progress 上传百分比回调
 * @returns 通过校验的响应内容
 */
export async function uploadRequest<T>(action: string, guard: (value: unknown) => value is T, body: unknown, progress: (percentage: number) => void): Promise<T> {
  const loading = beginLoading('正在上传歌单…')
  try {
    return await new Promise<T>((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      xhr.open('POST', `/api.php?action=${encodeURIComponent(action)}`)
      xhr.withCredentials = true
      xhr.setRequestHeader('Content-Type', 'application/json')
      xhr.setRequestHeader('X-CSRF-Token', csrf())
      xhr.upload.onprogress = (event) => {
        if (event.lengthComputable) {
          const value = Math.round(event.loaded / event.total * 100)
          progress(value); loading.update(`正在上传歌单 ${value}%`)
        }
      }
      xhr.onload = () => {
        checkSession(xhr.status, action)
        try {
          const content = validate(decode(xhr.responseText, xhr.status, action), xhr.status, action, guard)
          progress(100); resolve(content)
        } catch (failure: unknown) { reject(failure) }
      }
      xhr.onerror = () => reject(new ApiError(ErrorType.Network))
      xhr.onabort = () => reject(new ApiError(ErrorType.Cancelled))
      xhr.ontimeout = () => reject(new ApiError(ErrorType.Timeout))
      xhr.send(JSON.stringify(body))
    })
  } finally { loading() }
}
