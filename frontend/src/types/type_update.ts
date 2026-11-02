import { isBoolean, isRecord, isString } from './type_api'

export interface ReleaseAsset { name: string; url: string; size: number; sha256: string }
export interface Release { version: string; title: string; description: string; published_at: string; url: string; assets: { patch?: ReleaseAsset; install?: ReleaseAsset } }
export interface UpdateCheck { current: string; repository: string; supported: boolean; available: boolean; can_update: boolean; reason: string; latest: Release | null; releases: Release[]; checked_at: number }
export interface UpdateJob { id: string; version: string; status: 'queued' | 'running' | 'completed' | 'failed'; stage: string; progress: number; downloaded: number; size: number; message: string; updated_at: number; backup_available: boolean }

/** 用途：校验 GitHub 附件；入参：未知数据；返回值：是否符合附件结构。 */
function isAsset(value: unknown): value is ReleaseAsset {
  return isRecord(value) && ['name', 'url', 'sha256'].every((key) => isString(value[key])) && typeof value.size === 'number' && Number.isSafeInteger(value.size) && value.size >= 0 && typeof value.url === 'string' && value.url.startsWith('https://github.com/')
}

/** 用途：校验正式版本；入参：未知数据；返回值：是否符合版本结构。 */
function isRelease(value: unknown): value is Release {
  return isRecord(value) && ['version', 'title', 'description', 'published_at', 'url'].every((key) => isString(value[key]))
    && typeof value.url === 'string' && value.url.startsWith('https://github.com/')
    && (Array.isArray(value.assets) && value.assets.length === 0 || isRecord(value.assets)
      && (value.assets.patch === undefined || isAsset(value.assets.patch)) && (value.assets.install === undefined || isAsset(value.assets.install)))
}

/** 用途：校验更新检测响应；入参：未知数据；返回值：是否符合检测结构。 */
export function isUpdateCheck(value: unknown): value is UpdateCheck {
  return isRecord(value) && isString(value.current) && isString(value.repository) && isString(value.reason)
    && ['supported', 'available', 'can_update'].every((key) => isBoolean(value[key])) && typeof value.checked_at === 'number'
    && (value.latest === null || isRelease(value.latest)) && Array.isArray(value.releases) && value.releases.every(isRelease)
}

/** 用途：校验更新任务；入参：未知数据；返回值：是否符合任务结构。 */
export function isUpdateJob(value: unknown): value is UpdateJob {
  return isRecord(value) && ['id', 'version', 'stage', 'message'].every((key) => isString(value[key])) && ['queued', 'running', 'completed', 'failed'].includes(String(value.status))
    && ['progress', 'downloaded', 'size', 'updated_at'].every((key) => typeof value[key] === 'number' && Number.isFinite(value[key]) && value[key] >= 0)
    && typeof value.progress === 'number' && value.progress <= 100 && isBoolean(value.backup_available)
}
export const isOptionalUpdateJob = (value: unknown): value is UpdateJob | null => value === null || isUpdateJob(value)
