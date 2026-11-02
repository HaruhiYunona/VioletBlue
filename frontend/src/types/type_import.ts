import { isRecord, isString } from './type_api'

/** 可编辑的文件预览行，保留原始行号。 */
export interface ImportRow { line: number; name: string; singer: string; tagText: string; claim: string; selected: boolean }
/** 解析器返回的工作表和歌曲数据。 */
export interface ImportSheet { current: string; rows: ImportRow[] }
/** 后台导入任务的公开进度。 */
export interface ImportTask { id: string; status: 'queued' | 'running' | 'completed' | 'failed' | 'cancelled'; total: number; processed: number; imported: number; skipped: number; message: string }

/** 校验解析器工作表消息；入参：未知消息；返回：是否为预览结果。 */
export function isImportSheet(value: unknown): value is ImportSheet {
  return isRecord(value) && isString(value.current) && Array.isArray(value.rows) && value.rows.every((row: unknown) => isRecord(row) && typeof row.line === 'number' && Number.isInteger(row.line) && ['name', 'singer', 'tagText', 'claim'].every((key) => isString(row[key])) && typeof row.selected === 'boolean')
}
/** 校验导入任务消息；入参：未知响应；返回：是否为任务进度。 */
export function isImportTask(value: unknown): value is ImportTask {
  return isRecord(value) && isString(value.id) && ['queued', 'running', 'completed', 'failed', 'cancelled'].includes(String(value.status)) && isString(value.message)
    && ['total', 'processed', 'imported', 'skipped'].every((key) => typeof value[key] === 'number' && Number.isSafeInteger(value[key]) && value[key] >= 0)
}
