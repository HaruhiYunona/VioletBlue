import { read, set_cptable, utils, type WorkBook } from 'xlsx'
import * as codepages from 'xlsx/dist/cpexcel.full.mjs'
import { isRecord } from '../types/type_api'
import { createPreview } from '../utils/util_batch'

set_cptable(codepages)

/** 解码 Excel 导出的文本编码；入参：文件字节；返回：文本。 */
function decodeText(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer)
  if (bytes[0] === 255 && bytes[1] === 254) return new TextDecoder('utf-16le').decode(bytes)
  if (bytes[0] === 254 && bytes[1] === 255) return new TextDecoder('utf-16be').decode(bytes)
  try { return new TextDecoder('utf-8', { fatal: true }).decode(bytes) }
  catch { return new TextDecoder('gb18030', { fatal: true }).decode(bytes) }
}

/** 返回第一张工作表预览；入参：已解析工作簿；返回：无，通过消息回传。 */
function preview(workbook: WorkBook): void {
  const name = workbook.SheetNames[0]
  if (!name || !workbook.Sheets[name]) throw new Error('文件中没有第一张工作表')
  if (!workbook.Sheets[name]['!ref']) throw new Error('第一张工作表为空，请将需要导入的歌单放在表一')
  const matrix = utils.sheet_to_json<unknown[]>(workbook.Sheets[name], { header: 1, raw: false, defval: '', blankrows: true })
  const table = matrix.map((row) => row.map((value) => typeof value === 'string' ? value : typeof value === 'number' || typeof value === 'boolean' ? String(value) : ''))
  postMessage({ type: 'result', data: { current: name, rows: createPreview(table) } })
}

// 在独立线程解析整份文件，主页面保持可操作；不执行工作簿公式或宏。
addEventListener('message', (event: MessageEvent<unknown>) => {
  try {
    if (!isRecord(event.data)) throw new Error('解析请求格式不正确')
    let workbook: WorkBook
    if (event.data.type === 'text' && typeof event.data.text === 'string') {
      workbook = read(event.data.text, { type: 'string', raw: true, FS: '\t', cellFormula: false })
    } else if (event.data.type === 'file' && event.data.buffer instanceof ArrayBuffer && typeof event.data.name === 'string') {
      const extension = event.data.name.toLowerCase().split('.').pop()
      const bytes = new Uint8Array(event.data.buffer)
      if (extension === 'xlsx') {
        if (bytes[0] !== 0x50 || bytes[1] !== 0x4b) throw new Error('文件内容不是有效的 XLSX 工作簿')
        workbook = read(event.data.buffer, { sheets: 0, cellFormula: false, bookVBA: false })
      } else if (extension === 'xls') {
        if (bytes[0] !== 0xd0 || bytes[1] !== 0xcf) throw new Error('文件内容不是有效的 XLS 工作簿')
        workbook = read(event.data.buffer, { sheets: 0, cellFormula: false, bookVBA: false })
      } else if (['csv', 'tsv', 'txt'].includes(extension ?? '')) {
        workbook = read(decodeText(event.data.buffer), { type: 'string', raw: true, ...(extension === 'csv' ? {} : { FS: '\t' }), cellFormula: false })
      } else throw new Error('请选择 XLSX、XLS、CSV 或 TSV 文件')
    } else throw new Error('解析请求格式不正确')
    preview(workbook)
  } catch (failure: unknown) { postMessage({ type: 'error', message: failure instanceof Error ? failure.message : '文件解析失败' }) }
})
