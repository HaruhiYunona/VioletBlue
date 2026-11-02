<script setup lang="ts">
/** GitHub Release 检测、自动更新及任务恢复页面。 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { messageOf, request } from '../api/api_client'
import { isRecord, isString } from '../types/type_api'
import { isOptionalUpdateJob, isUpdateCheck, isUpdateJob, type UpdateCheck, type UpdateJob } from '../types/type_update'
import { notify } from '../state/state_site'

const version = ref(''); const error = ref(''); const busy = ref(false); const starting = ref(false)
const check = ref<UpdateCheck | null>(null); const job = ref<UpdateJob | null>(null); const polling = ref(false)
const needsReload = ref(false)
const running = computed(() => job.value !== null && ['queued', 'running'].includes(job.value.status))
const repositoryUrl = computed(() => `https://github.com/${check.value?.repository ?? 'HaruhiYunona/VioletBlue'}`)
let stopped = false
const isVersion = (value: unknown): value is { version: string; res: string } => isRecord(value) && isString(value.version) && isString(value.res)

/** 用途：刷新正式版本及更新说明；入参：是否刷新服务器缓存；返回值：检测任务。 */
async function refresh(force = false): Promise<void> {
  if (busy.value || running.value || starting.value) return
  busy.value = true; error.value = ''
  try { check.value = await request('getUpdate', isUpdateCheck, { force }); version.value = check.value.current }
  catch (failure: unknown) { error.value = messageOf(failure) }
  finally { busy.value = false }
}

/** 用途：查询服务器更新进度；入参：无；返回值：轮询结束的任务。 */
async function poll(): Promise<void> {
  if (polling.value || !job.value) return
  polling.value = true
  try {
    while (!stopped && job.value && ['queued', 'running'].includes(job.value.status)) {
      const result: UpdateJob | null = await request('getUpdateStatus', isOptionalUpdateJob, { id: job.value.id })
      if (stopped) return
      job.value = result
      if (result?.status === 'completed') {
        version.value = result.version; needsReload.value = true
        if (check.value?.latest?.version === result.version) check.value = { ...check.value, current: result.version, available: false, can_update: false, reason: '当前已是最新版本' }
        notify('更新成功，请刷新页面加载新版本'); break
      }
      if (result?.status === 'failed') { error.value = result.message; break }
      await new Promise<void>((resolve) => setTimeout(resolve, 800))
    }
  } catch (failure: unknown) { error.value = messageOf(failure) }
  finally { polling.value = false }
}

/** 用途：创建一次更新任务，后续只查询进度；入参：无；返回值：任务创建结果。 */
async function update(): Promise<void> {
  if (!check.value?.can_update || !check.value.latest || running.value || starting.value) return
  starting.value = true; error.value = ''
  try { job.value = await request('startUpdate', isUpdateJob, { version: check.value.latest.version }); void poll() }
  catch (failure: unknown) { error.value = messageOf(failure) }
  finally { starting.value = false }
}

/** 用途：加载完成后的新程序；入参：无；返回值：无。 */
function reload(): void { window.location.reload() }

onMounted(async () => {
  try {
    version.value = (await request('getVersion', isVersion)).version
    job.value = await request('getUpdateStatus', isOptionalUpdateJob)
    if (running.value) { void poll(); return }
  } catch (failure: unknown) { error.value = messageOf(failure) }
  if (!stopped) await refresh()
})
onBeforeUnmount(() => { stopped = true })
</script>

<template>
  <h1 class="section-title mb-7">日志与更新</h1>
  <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div><p class="text-sm text-slate-500">当前版本</p><p class="mt-1 text-2xl font-bold text-slate-800">{{ version || '正在读取…' }}</p></div>
      <button type="button" class="quiet-button" :disabled="busy || running || starting" @click="refresh(true)">{{ busy ? '正在检查…' : '检查更新' }}</button>
    </div>
    <p v-if="error" role="alert" class="mt-5 whitespace-pre-wrap text-sm text-red-700">{{ error }}</p>
    <p v-if="busy" role="status" class="mt-4 text-sm text-slate-500">正在检查更新…</p>
    <template v-if="check">
      <p class="mt-5 text-sm" :class="check.available ? 'text-violet-700' : 'text-slate-500'">{{ check.available ? `发现新版本 ${check.latest?.version}` : check.reason }}</p>
      <template v-if="check.available && check.latest">
        <h2 class="mt-4 text-lg font-bold text-slate-800">{{ check.latest.title }}</h2>
        <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-600">{{ check.latest.description || '此版本未填写发布说明。' }}</p>
      </template>
      <p v-if="check.available && !check.can_update" class="mt-2 text-sm text-amber-700">{{ check.reason }}</p>
      <div class="mt-4 flex flex-wrap gap-3">
        <button v-if="check.can_update && !(job?.status === 'completed' && job.version === check.latest?.version)" type="button" class="action-button" :disabled="starting || running" @click="update">{{ starting ? '正在准备…' : running ? '更新进行中…' : '立即更新' }}</button>
        <a v-if="check.latest" :href="check.latest.url" target="_blank" rel="noopener noreferrer" class="quiet-button">查看 Release</a>
      </div>
    </template>
    <a :href="repositoryUrl" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-sm text-violet-600 underline">前往项目仓库</a>
  </section>
  <section v-if="job" aria-label="更新进度" class="mt-5 rounded-xl border border-violet-100 bg-violet-50/50 p-5">
    <div class="flex flex-wrap justify-between gap-2 text-sm"><strong>{{ job.status === 'completed' ? '更新完成' : job.status === 'failed' ? '更新失败' : '服务器正在更新' }}</strong><span>{{ job.progress }}%</span></div>
    <div role="progressbar" aria-label="更新进度" :aria-valuenow="job.progress" aria-valuemin="0" aria-valuemax="100" class="mt-3 h-2 overflow-hidden rounded-full bg-violet-100"><div class="h-full bg-violet-500 transition-all" :style="{ width: `${job.progress}%` }"></div></div>
    <p class="mt-3 whitespace-pre-wrap text-sm text-slate-600">{{ job.message }}</p>
    <p v-if="job.stage === 'download'" class="mt-2 text-xs text-slate-500">{{ (job.downloaded / 1048576).toFixed(2) }} / {{ (job.size / 1048576).toFixed(2) }} MB</p>
    <button v-if="job.status === 'completed' && needsReload" type="button" class="action-button mt-4" @click="reload">刷新使用新版本</button>
    <button v-if="running && !polling" type="button" class="quiet-button mt-4" @click="poll">继续查看进度</button>
  </section>
  <ol v-if="check?.releases.length" class="mt-7 space-y-5">
    <li v-for="release in check.releases.filter((item) => !check?.available || item.version !== check.latest?.version)" :key="release.version" class="rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex flex-wrap items-baseline gap-3"><h2 class="text-lg font-bold">{{ release.title }}</h2><span class="text-xs text-slate-500">{{ release.version }} · {{ release.published_at.slice(0, 10) }}</span></div>
      <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-600">{{ release.description || '此版本未填写发布说明。' }}</p>
    </li>
  </ol>
</template>
