<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { request, messageOf } from '../api/api_client'
import { siteState } from '../state/state_site'
import { isMigrationDatabases, isMigrationPreview, isMigrationResult, type MigrationDatabase, type MigrationPreview, type MigrationResult } from '../types/type_migration'

const router = useRouter()
const databases = ref<MigrationDatabase[]>([])
const selected = ref('')
const preview = ref<MigrationPreview | null>(null)
const result = ref<MigrationResult | null>(null)
const loading = ref(true)
const applying = ref(false)
const error = ref('')
const activeMode = ref<'deduplicate' | 'merge'>('merge')
const current = computed(() => databases.value.find((entry) => key(entry) === selected.value))

function key(entry: MigrationDatabase): string { return JSON.stringify([entry.database, entry.prefix]) }

/** 读取目标的重复歌曲预览；返回：预览请求。 */
async function inspect(): Promise<void> {
  const target = current.value
  preview.value = null; result.value = null; error.value = ''
  if (!target) return
  loading.value = true
  try { preview.value = await request('previewMigration', isMigrationPreview, { database: target.database, prefix: target.prefix }) }
  catch (failure: unknown) { error.value = messageOf(failure) }
  finally { loading.value = false }
}

/** 发现当前账号可迁移的数据库；返回：列表及首次预览请求。 */
async function refresh(): Promise<void> {
  loading.value = true; error.value = ''
  try {
    databases.value = await request('getMigrationDatabases', isMigrationDatabases)
    const first = databases.value.find((entry) => entry.current) ?? databases.value[0]
    if (!databases.value.some((entry) => key(entry) === selected.value)) selected.value = first ? key(first) : ''
    await inspect()
  } catch (failure: unknown) { error.value = messageOf(failure) }
  finally { loading.value = false }
}

/** 根据已展示预览执行迁移；入参：处理方式；返回：迁移请求。 */
async function migrate(mode: 'deduplicate' | 'merge'): Promise<void> {
  const value = preview.value
  if (!value || applying.value) return
  applying.value = true; activeMode.value = mode; error.value = ''
  try {
    result.value = await request('applyMigration', isMigrationResult, { database: value.database, prefix: value.prefix, fingerprint: value.fingerprint, mode })
    if (result.value.login_required) siteState.authenticated = false
  } catch (failure: unknown) { error.value = messageOf(failure) }
  finally { applying.value = false }
}

function back(): void { void router.push('/admin') }
onMounted(refresh)
</script>

<template>
  <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div><p class="mb-1 text-xs font-medium tracking-widest text-violet-500">旧版升级</p><h1 class="section-title">数据库迁移</h1><p class="mt-2 text-sm leading-6 text-slate-500">先看看重复歌曲，再选择要保留哪些信息。</p></div>
    <RouterLink to="/admin" class="quiet-button text-sm">返回控制台</RouterLink>
  </div>
  <section v-if="result" role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-slate-800">
    <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-emerald-100 text-xl text-emerald-700">✓</div>
    <h2 class="text-lg font-semibold">迁移完成</h2>
    <p class="mt-2 text-sm leading-7">{{ result.database }} / {{ result.prefix }}：保留 {{ result.total }} 首歌曲，处理 {{ result.removed }} 条重复记录。</p>
    <p class="mt-2 text-sm">管理员密码已重置为 <strong>123456</strong>，登录后可在网站设置中修改。</p>
    <details class="mt-4 text-xs text-slate-500"><summary class="cursor-pointer">查看原表备份</summary><p class="mt-2 break-all leading-6">{{ result.backups.song }}<br>{{ result.backups.others }}</p></details>
    <button class="action-button mt-6" @click="back">返回控制台</button>
  </section>
  <template v-else>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-4 flex items-center justify-between gap-3"><h2 class="font-semibold text-slate-800">可迁移的数据库</h2><button type="button" class="text-xs text-violet-600 underline underline-offset-4 disabled:opacity-50" :disabled="loading || applying" @click="refresh">重新检查</button></div>
      <label for="migration-database" class="mb-2 block text-xs text-slate-500">数据库 / 表前缀</label>
      <select id="migration-database" v-model="selected" class="form-input w-full" :disabled="loading || applying || !databases.length" @change="inspect">
        <option v-if="!databases.length" value="">{{ loading ? '正在检查数据库…' : '未找到可迁移的歌单数据表' }}</option>
        <option v-for="entry in databases" :key="key(entry)" :value="key(entry)">{{ entry.database }} / {{ entry.prefix }}{{ entry.current ? '（当前站点）' : '' }} · {{ entry.total }} 首</option>
      </select>
      <div v-if="preview" class="mt-5 grid grid-cols-3 divide-x divide-slate-100 rounded-xl bg-slate-50 py-4 text-center">
        <div><p class="text-2xl font-semibold text-slate-800">{{ preview.total }}</p><p class="mt-1 text-xs text-slate-500">现有歌曲</p></div>
        <div><p class="text-2xl font-semibold text-amber-600">{{ preview.groups.length }}</p><p class="mt-1 text-xs text-slate-500">重复组</p></div>
        <div><p class="text-2xl font-semibold text-violet-600">{{ preview.remaining }}</p><p class="mt-1 text-xs text-slate-500">迁移后保留</p></div>
      </div>
      <p v-if="preview" class="mt-3 break-all text-xs leading-6 text-slate-400">{{ preview.song_table }} · {{ preview.others_table }}</p>
    </section>
    <p v-if="error" role="alert" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
    <p v-if="loading" role="status" class="py-10 text-center text-sm text-slate-500">正在读取歌单和重复歌曲…</p>
    <section v-if="preview && !loading" class="mt-7">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="font-semibold text-slate-800">重复歌曲 <span class="ml-1 text-sm font-normal text-slate-400">{{ preview.groups.length }} 组 · {{ preview.duplicate_count }} 条多余记录</span></h2><p class="mt-1 text-xs leading-6 text-slate-500">只有歌名和歌手都一致才算重复，保留最早的序号。</p></div>
        <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs"><button class="rounded-md px-3 py-2" :class="activeMode === 'deduplicate' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500'" @click="activeMode = 'deduplicate'">去重预览</button><button class="rounded-md px-3 py-2" :class="activeMode === 'merge' ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-500'" @click="activeMode = 'merge'">合并预览</button></div>
      </div>
      <div v-if="!preview.groups.length" class="rounded-xl border border-dashed border-emerald-200 bg-emerald-50/50 px-5 py-8 text-center text-sm text-emerald-700">没有发现重复歌曲，可以直接迁移。</div>
      <div v-else class="space-y-4">
        <article v-for="group in preview.groups" :key="JSON.stringify([group.name, group.singer])" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-semibold text-slate-800">{{ group.name }} <span class="mx-1 font-normal text-slate-300">/</span> {{ group.singer }}</h3><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs text-amber-700">{{ group.rows.length }} 条记录</span></header>
          <div class="overflow-x-auto"><table class="w-full min-w-[480px] text-left text-xs"><thead class="bg-slate-50 text-slate-400"><tr><th class="w-24 px-5 py-3 font-normal">序号</th><th class="px-4 py-3 font-normal">分类 / 标签</th><th class="px-4 py-3 font-normal">备注</th><th class="w-24 px-4 py-3 font-normal">处理</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(row, index) in group.rows" :key="row.uid" :class="activeMode === 'deduplicate' && index > 0 ? 'bg-red-50/30 text-slate-400' : 'text-slate-600'"><td class="px-5 py-3 font-mono">#{{ row.uid }}</td><td class="px-4 py-3"><div class="flex flex-wrap gap-1"><span v-for="tag in row.tags" :key="tag" class="rounded bg-slate-100 px-2 py-1 text-slate-500">{{ tag }}</span><span v-if="!row.tags.length" class="text-slate-300">—</span></div></td><td class="max-w-64 break-words px-4 py-3 leading-6">{{ row.note || '—' }}</td><td class="px-4 py-3"><span :class="index === 0 ? 'text-emerald-600' : activeMode === 'merge' ? 'text-violet-500' : 'text-red-400'">{{ index === 0 ? '保留' : activeMode === 'merge' ? '合并后删除' : '删除' }}</span></td></tr></tbody></table></div>
          <div v-if="activeMode === 'merge'" class="border-t border-violet-100 bg-violet-50/50 px-5 py-4"><p class="mb-2 text-xs font-medium text-violet-700">合并结果 · #{{ group.merged.uid }}</p><div class="flex flex-wrap gap-1"><span v-for="tag in group.merged.tags" :key="tag" class="rounded bg-white px-2 py-1 text-xs text-violet-600 ring-1 ring-violet-100">{{ tag }}</span></div><p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ group.merged.note || '无备注' }}</p></div>
        </article>
      </div>
      <section class="mt-6 rounded-2xl border border-violet-100 bg-violet-50/40 p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-slate-800">选择迁移方式</h2>
        <p class="mt-2 text-xs leading-6 text-slate-500">去重：删除序号靠后的重复歌曲。合并：标签合并去重，备注优先保留有内容的，不同备注用逗号连接；其他字段优先补全空值。</p>
        <p class="mt-2 text-xs leading-6 text-slate-500">公告和其他设置会保留，原表自动备份。迁移后管理员密码重置为 <span class="font-medium text-slate-700">123456</span>。</p>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row"><button type="button" title="将序号更靠后的重复歌曲删除并迁移数据库" class="rounded-lg border border-violet-200 bg-white px-5 py-3 text-sm font-medium text-violet-700 disabled:opacity-50" :disabled="applying" @click="migrate('deduplicate')">去重迁移</button><button type="button" class="rounded-lg bg-violet-600 px-5 py-3 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-50" :disabled="applying" @click="migrate('merge')">{{ applying ? '正在迁移…' : '合并迁移' }}</button><button type="button" class="rounded-lg px-5 py-3 text-sm text-slate-500 hover:bg-white disabled:opacity-50" :disabled="applying" @click="back">取消</button></div>
      </section>
    </section>
  </template>
</template>
