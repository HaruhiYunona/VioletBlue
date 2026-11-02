<script setup lang="ts">
/** 文件解析预览、整份提交和异步入库进度页面。 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { messageOf, request, uploadRequest } from '../api/api_client'
import { isRecord } from '../types/type_api'
import { isImportSheet, isImportTask, type ImportRow, type ImportTask } from '../types/type_import'
import { notify } from '../state/state_site'
import { inspectRows } from '../utils/util_batch'

const text = ref(''); const error = ref(''); const parsing = ref(false); const uploading = ref(false); const uploadPercent = ref(0)
const filename = ref(''); const sheet = ref(''); const rows = ref<ImportRow[]>([])
const defaultTags = ref('未分类'); const page = ref(1); const task = ref<ImportTask | null>(null)
const checked = computed(() => inspectRows(rows.value, defaultTags.value))
const selected = computed(() => checked.value.filter((item) => item.row.selected && !item.error))
const invalidCount = computed(() => checked.value.filter((item) => item.error).length)
const pageCount = computed(() => Math.max(1, Math.ceil(rows.value.length / 50)))
const visible = computed(() => checked.value.slice((page.value - 1) * 50, page.value * 50))
const running = computed(() => task.value !== null && ['queued', 'running'].includes(task.value.status))
const progress = computed(() => task.value?.total ? Math.floor(task.value.processed / task.value.total * 100) : 0)
let worker: Worker | undefined
let stop = false
const polling = ref(false)
let fileRevision = 0

/** 创建可取消的文件解析线程；返回：解析器。 */
function parser(): Worker {
  worker?.terminate()
  worker = new Worker(new URL('../workers/worker_spreadsheet.ts', import.meta.url), { type: 'module' })
  worker.onmessage = (event: MessageEvent<unknown>) => {
    parsing.value = false
    if (!isRecord(event.data)) { error.value = '解析器返回内容无效'; return }
    if (event.data.type === 'error') { error.value = String(event.data.message); return }
    if (!isImportSheet(event.data.data)) { error.value = '解析器返回内容无效'; return }
    sheet.value = event.data.data.current; rows.value = event.data.data.rows; page.value = 1
    const firstError = checked.value.find((item) => item.error)
    for (const item of checked.value) if (item.error) item.row.selected = false
    if (firstError) error.value = firstError.error
  }
  worker.onerror = () => { parsing.value = false; error.value = '文件解析失败，请检查文件是否损坏或加密' }
  return worker
}
/** 解析整份文件；入参：文件；返回：读取完成的任务。 */
async function readFile(file: File): Promise<void> {
  if (uploading.value || running.value) return
  error.value = ''; rows.value = []; sheet.value = ''; filename.value = file.name; parsing.value = true
  const current = ++fileRevision
  try {
    if (!file.size) throw new Error('文件不能为空')
    if (!/\.(xlsx|xls|csv|tsv|txt)$/i.test(file.name)) throw new Error('请选择 XLSX、XLS、CSV 或 TSV 文件')
    const buffer = await file.arrayBuffer()
    if (current !== fileRevision || stop) return
    parser().postMessage({ type: 'file', name: file.name, buffer }, [buffer])
  } catch (failure: unknown) { parsing.value = false; error.value = messageOf(failure) }
}
/** 处理文件选择；入参：输入事件；返回：无。 */
function choose(event: Event): void {
  if (!(event.target instanceof HTMLInputElement)) return
  const file = event.target.files?.[0]; event.target.value = ''
  if (file) void readFile(file)
}
/** 处理拖入文件；入参：拖放事件；返回：无。 */
function drop(event: DragEvent): void {
  const file = event.dataTransfer?.files[0]
  if (file) void readFile(file)
}
/** 将粘贴文本送入解析线程；返回：无。 */
function previewText(): void {
  if (!text.value.trim()) { error.value = '请先粘贴歌单内容'; return }
  error.value = ''; filename.value = '粘贴的歌单'; sheet.value = ''; rows.value = []; parsing.value = true
  parser().postMessage({ type: 'text', text: text.value })
}
/** 修改全部有效行的选择状态；入参：是否选择；返回：无。 */
function selectAll(value: boolean): void { for (const item of checked.value) item.row.selected = value && !item.error }

/** 只查询任务状态，不再发送歌曲数据；返回：查询循环结束的任务。 */
async function poll(): Promise<void> {
  if (polling.value || !task.value) return
  polling.value = true
  try {
    while (!stop && task.value && ['queued', 'running'].includes(task.value.status)) {
      const result: ImportTask = await request('importStatus', isImportTask, { id: task.value.id })
      if (task.value?.status === 'cancelled') break
      task.value = result
      if (result.status === 'completed') { notify(`导入完成：新增 ${result.imported} 首，跳过重复 ${result.skipped} 首`); localStorage.removeItem('vb-import-task'); break }
      if (result.status === 'failed') { error.value = result.message; localStorage.removeItem('vb-import-task'); break }
      if (result.status === 'cancelled') { localStorage.removeItem('vb-import-task'); break }
      await new Promise<void>((resolve) => setTimeout(resolve, 600))
    }
  } catch (failure: unknown) { error.value = messageOf(failure) }
  finally { polling.value = false }
}
/** 一次上传所有选中数据，服务器异步入库；返回：任务创建完成的 Promise。 */
async function submit(): Promise<void> {
  if (uploading.value || running.value || !selected.value.length) return
  error.value = ''; uploading.value = true; uploadPercent.value = 0
  try {
    task.value = await uploadRequest('startImport', isImportTask, { list: selected.value.map((item) => item.song) }, (value) => { uploadPercent.value = value })
    localStorage.setItem('vb-import-task', task.value.id)
    void poll()
  } catch (failure: unknown) { error.value = messageOf(failure) }
  finally { uploading.value = false }
}
/** 取消服务器任务，保留已完成的歌曲；返回：请求完成的任务。 */
async function cancel(): Promise<void> {
  if (!task.value) return
  try { task.value = await request('cancelImport', isImportTask, { id: task.value.id }); localStorage.removeItem('vb-import-task') }
  catch (failure: unknown) { error.value = messageOf(failure) }
}
onMounted(async () => {
  const id = localStorage.getItem('vb-import-task')
  if (!id) return
  try { task.value = await request('importStatus', isImportTask, { id }); if (!stop) void poll() }
  catch { localStorage.removeItem('vb-import-task') }
})
onBeforeUnmount(() => { stop = true; fileRevision++; worker?.terminate() })
</script>

<template>
  <h1 class="section-title mb-6">批量新增歌曲</h1>
  <div class="rounded-xl border-2 border-dashed border-violet-200 bg-violet-50/50 p-6 text-center" @dragover.prevent @drop.prevent="drop">
    <label for="batch-file" class="action-button cursor-pointer">选择歌单文件</label>
    <input id="batch-file" type="file" accept=".xlsx,.xls,.csv,.tsv,.txt" class="sr-only" :disabled="parsing || uploading || running" @change="choose" />
    <p class="mt-3 text-sm text-slate-600">支持 Excel（XLSX / XLS）、CSV 和 TSV，也可将文件拖到这里。</p>
    <p class="mt-2 text-xs text-slate-500">自动识别歌名、歌手、标签和备注；无表头时按这四列顺序读取。</p>
    <p class="mt-2 text-xs text-slate-500">Excel 仅导入第一张工作表，其他工作表不会读取。</p>
    <p v-if="filename" class="mt-3 break-all text-sm font-medium">{{ filename }}</p>
  </div>
  <details class="mt-5 rounded-lg border border-slate-200 p-4"><summary class="cursor-pointer text-sm font-medium">粘贴表格内容</summary>
    <label for="batch-songs" class="form-label">歌单列表</label><textarea id="batch-songs" v-model="text" :disabled="parsing || uploading || running" rows="6" class="form-input resize-y font-mono text-sm" placeholder="从表格复制歌名、歌手、标签和备注"></textarea>
    <button type="button" class="quiet-button mt-3" :disabled="parsing || uploading || running" @click="previewText">生成预览</button>
  </details>
  <p v-if="parsing" role="status" class="mt-5 text-sm text-violet-700">正在解析并生成预览…</p>
  <p v-if="error" role="alert" class="mt-5 whitespace-pre-wrap text-sm text-red-700">{{ error }}</p>
  <section v-if="rows.length" class="mt-6">
    <div class="mb-4 flex flex-wrap items-end gap-4">
      <span v-if="sheet" class="text-sm text-slate-500">当前读取：{{ sheet }}（第一张工作表）</span>
      <label class="text-sm">缺省标签<input v-model="defaultTags" :disabled="uploading || running" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2" /></label>
      <span class="text-sm text-slate-500">共 {{ rows.length }} 首 · 选中 {{ selected.length }} 首 · {{ invalidCount }} 行需检查</span>
    </div>
    <p class="mb-3 text-xs leading-5 text-slate-500">可直接修改预览。歌名、歌手最多 300 字符；每个标签最多 100 字符，用逗号分隔。标签包含逗号时可填写 JSON 数组。</p>
    <fieldset :disabled="uploading || running" class="min-w-0">
      <div class="mb-3 flex gap-3"><button type="button" class="quiet-button" @click="selectAll(true)">全选有效歌曲</button><button type="button" class="quiet-button" @click="selectAll(false)">取消全选</button></div>
      <div class="max-h-[60dvh] overflow-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[700px] text-left text-sm"><thead class="sticky top-0 z-10 bg-slate-50 text-slate-500"><tr><th class="p-3 whitespace-nowrap">选择 / 行</th><th class="p-3">歌名</th><th class="p-3">歌手</th><th class="p-3">标签</th><th class="p-3">备注</th></tr></thead><tbody>
          <template v-for="item in visible" :key="item.row.line"><tr :class="item.error ? 'bg-red-50' : 'border-t border-slate-100'"><td class="p-3"><label class="flex items-center gap-2"><input v-model="item.row.selected" type="checkbox" class="accent-violet-600" :disabled="Boolean(item.error)" :aria-label="`选择第 ${item.row.line} 行`" />{{ item.row.line }}</label></td>
            <td v-for="field in (['name', 'singer', 'tagText', 'claim'] as const)" :key="field" class="p-2"><input v-model="item.row[field]" :aria-label="`第 ${item.row.line} 行${({ name: '歌名', singer: '歌手', tagText: '标签', claim: '备注' })[field]}`" :placeholder="field === 'tagText' ? `默认：${defaultTags}` : ''" class="w-full min-w-[130px] rounded-md border border-slate-200 bg-white px-2 py-2 text-slate-800" /></td></tr>
            <tr v-if="item.error" class="bg-red-50"><td colspan="5" class="px-3 pb-3 text-xs text-red-700">{{ item.error }}</td></tr></template>
        </tbody></table>
      </div>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-3 text-sm"><button type="button" class="quiet-button" :disabled="page <= 1" @click="page--">上一页</button><span>{{ page }} / {{ pageCount }}</span><button type="button" class="quiet-button" :disabled="page >= pageCount" @click="page++">下一页</button></div><button type="button" class="action-button" :disabled="!selected.length || parsing || uploading || running" @click="submit">确认添加 {{ selected.length }} 首</button></div>
    </fieldset>
  </section>
  <section v-if="uploading || task" class="mt-6 rounded-xl border border-violet-100 bg-violet-50/50 p-5" aria-label="导入进度">
    <p class="text-sm font-medium">{{ uploading ? `正在上传 ${uploadPercent}%` : task?.status === 'completed' ? '导入完成' : task?.status === 'failed' ? '导入失败' : task?.status === 'cancelled' ? '导入已取消' : '服务器正在导入' }}</p>
    <div class="mt-3 h-2 overflow-hidden rounded-full bg-violet-100" role="progressbar" :aria-valuenow="uploading ? uploadPercent : progress" aria-valuemin="0" aria-valuemax="100" :aria-label="uploading ? '上传进度' : '入库进度'"><div class="h-full bg-violet-500 transition-all" :style="{ width: `${uploading ? uploadPercent : progress}%` }"></div></div>
    <p v-if="task" class="mt-3 text-xs text-slate-600">已处理 {{ task.processed }} / {{ task.total }} 首，新增 {{ task.imported }} 首，跳过重复 {{ task.skipped }} 首。{{ task.message }}</p>
    <div class="mt-3 flex gap-3"><button v-if="running" type="button" class="quiet-button" @click="cancel">取消剩余导入</button><button v-if="running && !polling" type="button" class="quiet-button" @click="poll">刷新进度</button></div>
  </section>
</template>
