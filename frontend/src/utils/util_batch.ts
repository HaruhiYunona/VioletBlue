/** 批量歌曲表格文本解析。 */
import { isStrings, type SongInput } from '../types/type_api'
import type { ImportRow } from '../types/type_import'

/** 解析逗号分隔或 JSON 数组标签；入参：标签文本；返回：去重后的标签。 */
export function parseTags(text: string): string[] {
  let tags: string[]
  if (text.trim().startsWith('[')) {
    const value: unknown = JSON.parse(text)
    if (!isStrings(value)) throw new Error('标签 JSON 必须为字符串数组')
    tags = value
  } else tags = text.split(/[,，;；\r\n]+/u)
  return [...new Set(tags.map((tag) => tag.trim()).filter((tag) => tag && tag !== '全部'))]
}

/** 自动识别表头并保留数据行号；入参：工作表文本矩阵；返回：可编辑预览。 */
export function createPreview(table: string[][]): ImportRow[] {
  const firstIndex = table.findIndex((row) => row.some((cell) => cell.trim()))
  if (firstIndex < 0) throw new Error('文件中没有歌曲数据')
  const header = table[firstIndex].map((cell) => cell.trim().toLowerCase().replace(/[（(].*$/, '').replace(/[\s_-]/g, ''))
  const aliases = [['歌名', '歌曲', '歌曲名', '歌曲名称', '曲名', '曲目', 'name', 'song', 'title'], ['歌手', '歌手名', '歌手名称', '演唱者', '艺术家', 'singer', 'artist'], ['标签', '分类', '歌曲分类', '歌曲标签', 'tags', 'tag', 'type', 'genre'], ['备注', '冠名', '备注信息', 'claim', 'notice', 'note', 'remark']]
  const columns = aliases.map((names) => header.findIndex((cell) => names.includes(cell)))
  const hasHeader = columns[0] >= 0 && columns[1] >= 0
  const indices = hasHeader ? columns : [0, 1, 2, 3]
  const result: ImportRow[] = []
  for (let index = firstIndex + (hasHeader ? 1 : 0); index < table.length; index++) {
    const cells = table[index]
    if (!cells.some((cell) => cell.trim())) continue
    const [name, singer, tagText, claim] = indices.map((column) => column < 0 ? '' : (cells[column] ?? ''))
    result.push({ line: index + 1, name: name.trim(), singer: singer.trim(), tagText, claim, selected: true })
  }
  if (!result.length) throw new Error('文件中只有表头，没有歌曲数据')
  return result
}

/** 检查预览字段及文件内重复项；入参：预览行、缺省标签；返回：歌曲和错误信息。 */
export function inspectRows(rows: ImportRow[], defaultTags = '未分类'): { row: ImportRow; song: SongInput; error: string }[] {
  const seen = new Map<string, number>()
  return rows.map((row) => {
    const song: SongInput = { name: row.name.trim(), singer: row.singer.trim(), tags: [], claim: row.claim.trim() }
    let error = ''
    try {
      song.tags = parseTags(row.tagText.trim() || defaultTags)
      if (!song.name || !song.singer) error = '歌名和歌手不能为空'
      else if ([...song.name].length > 300 || [...song.singer].length > 300) error = '歌名和歌手最长 300 字符'
      else if ([...song.claim].length > 5000) error = '备注最长 5000 字符'
      else if (!song.tags.length || song.tags.length > 100 || song.tags.some((tag) => [...tag].length > 100)) error = '填写 1–100 个标签，每个最长 100 字符'
      else if ([song.name, song.singer, song.claim].some((value) => /[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u.test(value)) || song.tags.some((tag) => /[\x00-\x1f\x7f]/u.test(tag))) error = '包含不可见控制字符'
      if (!error) {
        const key = JSON.stringify([song.name, song.singer].map((value) => value.normalize('NFKD').replace(/\p{M}/gu, '').toLocaleLowerCase()))
        const previous = seen.get(key)
        if (previous !== undefined) error = `与第 ${previous} 行重复`
        else seen.set(key, row.line)
      }
    } catch (failure: unknown) { error = failure instanceof Error ? failure.message : '标签格式不正确' }
    return { row, song, error: error ? `第 ${row.line} 行：${error}` : '' }
  })
}

/** 解析粘贴的制表符文本；入参：表格文本；返回：校验后的歌曲。 */
export function parseBatch(text: string): SongInput[] {
  const rows = createPreview(text.split(/\r?\n/).map((line) => line.split('\t')))
  const checked = inspectRows(rows)
  const invalid = checked.find((item) => item.error)
  if (invalid) throw new Error(invalid.error)
  return checked.map((item) => item.song)
}
