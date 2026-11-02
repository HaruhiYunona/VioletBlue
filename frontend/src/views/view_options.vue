<script setup lang="ts">
/** 公告、分类和管理密码编辑页面。 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { messageOf, request } from '../api/api_client'
import { isBoolean, isString, isStrings } from '../types/type_api'
import { notify, siteState } from '../state/state_site'
const notice = ref(''); const password = ref(''); const tags = ref<string[]>([]); const newTag = ref(''); const busy = ref(true); const error = ref(''); const router = useRouter()
const confirmation = '我已经知道后果，并决定如此'
const clearOpen = ref(false); const clearText = ref(''); const clearBusy = ref(false); const clearError = ref('')
const clearInput = ref<HTMLInputElement | null>(null); const clearTrigger = ref<HTMLButtonElement | null>(null)
const clearDialog = ref<HTMLElement | null>(null)
const canClear = computed(() => clearText.value === confirmation && !clearBusy.value)
let previousOverflow = ''
onMounted(async () => {
  try { const [text, values] = await Promise.all([request('getNotice', isString), request('getTagList', isStrings)]); notice.value = new DOMParser().parseFromString(text.replace(/<br\s*\/?>/gi, '\n'), 'text/html').body.textContent ?? ''; tags.value = values.filter((tag) => tag !== '全部') }
  catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
})
/**
 * 添加非空且未重复的新分类。
 * @returns 无
 */
function addTag(): void {
  const tag = newTag.value.trim()
  if (!tag || tag === '全部' || tags.value.includes(tag)) return
  if ([...tag].length > 100 || tags.value.length >= 100) { error.value = '每个标签最多 100 字符，最多 100 个标签'; return }
  tags.value.push(tag); newTag.value = ''
}
/**
 * 保存公告和分类，可选更新管理密码。
 * @returns 保存完成的任务
 */
async function save(): Promise<void> {
  busy.value = true; error.value = ''
  try {
    await request('changeSetting', isBoolean, { config: { notice: notice.value, tags: tags.value, passWd: password.value } })
    if (password.value) { siteState.authenticated = false; password.value = ''; notify('设置已保存，请使用新密码登录'); await router.replace('/admin/login') }
    else notify('网站设置已保存')
  } catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
/** 打开清空确认框并聚焦输入；返回：完成聚焦的任务。 */
async function openClear(): Promise<void> {
  if (busy.value || clearOpen.value) return
  clearText.value = ''; clearError.value = ''; clearOpen.value = true
  previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'
  await nextTick(); clearInput.value?.focus()
}
/** 关闭确认框并恢复触发按钮焦点；返回：完成聚焦的任务。 */
async function closeClear(): Promise<void> {
  if (clearBusy.value) return
  clearOpen.value = false; clearText.value = ''; clearError.value = ''
  document.body.style.overflow = previousOverflow
  await nextTick(); clearTrigger.value?.focus()
}
/** 约束弹窗内的焦点并处理 Escape；入参：键盘事件；返回：无。 */
function dialogKeys(event: KeyboardEvent): void {
  if (event.key === 'Escape') { event.preventDefault(); void closeClear(); return }
  if (event.key !== 'Tab') return
  const controls = Array.from(clearDialog.value?.querySelectorAll<HTMLElement>('input:not(:disabled), button:not(:disabled)') ?? [])
  const first = controls[0]; const last = controls.at(-1)
  if (!first || !last) { event.preventDefault(); return }
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
}
/** 提交确认文字并清空歌单，失败时保留弹窗；返回：请求完成的任务。 */
async function clearList(): Promise<void> {
  if (!canClear.value) return
  clearBusy.value = true; clearError.value = ''
  try {
    await request('clearSongList', isBoolean, { confirmation: clearText.value })
    clearBusy.value = false; await closeClear(); notify('歌单已清空')
  } catch (failure: unknown) { clearError.value = messageOf(failure) }
  finally { clearBusy.value = false }
}
onBeforeUnmount(() => { if (clearOpen.value) document.body.style.overflow = previousOverflow })
</script>
<template>
  <div :inert="clearOpen">
  <h1 class="section-title mb-6">网站设置</h1>
  <form @submit.prevent="save">
    <fieldset :disabled="busy" class="min-w-0">
    <label class="form-label" for="new-password">管理密码</label><input id="new-password" v-model="password" type="password" autocomplete="new-password" minlength="6" maxlength="72" class="form-input" placeholder="留空保持当前密码" /><p class="form-help">修改密码后，所有已有管理会话将失效。</p>
    <label class="form-label" for="notice-text">网站公告</label><textarea id="notice-text" v-model="notice" maxlength="10000" rows="7" class="form-input resize-y"></textarea>
    <fieldset class="mt-6"><legend class="font-semibold">歌曲分类</legend><div class="mt-3 flex flex-wrap gap-2"><button v-for="tag in tags" :key="tag" type="button" class="tag-button" aria-pressed="true" :aria-label="`删除标签 ${tag}`" @click="tags = tags.filter((value) => value !== tag)">{{ tag }} ×</button></div><p class="form-help">点击标签移除。“全部”分类由系统提供。</p>
      <div class="mt-3 flex gap-2"><label for="new-tag" class="sr-only">新标签</label><input id="new-tag" v-model="newTag" maxlength="200" class="form-input min-w-0 flex-1" placeholder="输入新标签（最多100字符）" @keydown.enter.prevent="addTag" /><button type="button" class="action-button shrink-0" @click="addTag">增加</button></div>
    </fieldset>
    <p v-if="error" role="alert" class="mt-5 text-sm text-[var(--error-text)]">{{ error }}</p><button class="action-button mt-7" :disabled="busy">{{ busy ? '保存中…' : '保存设置' }}</button>
    </fieldset>
  </form>
  <section class="mt-10 rounded-xl border border-red-200 bg-red-50/50 p-5" aria-labelledby="clear-playlist-heading">
    <h2 id="clear-playlist-heading" class="text-base font-semibold text-red-700">清空歌单</h2>
    <p class="mt-2 text-sm leading-6 text-slate-600">删除当前站点的全部歌曲，公告、分类和账号设置会保留。</p>
    <button ref="clearTrigger" type="button" class="mt-4 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy" @click="openClear">清空歌单</button>
  </section>
  </div>
  <div v-if="clearOpen" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" @click.self="closeClear">
    <section ref="clearDialog" role="alertdialog" aria-modal="true" aria-labelledby="clear-dialog-title" aria-describedby="clear-dialog-description" class="max-h-[90dvh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 text-slate-800 shadow-xl" @keydown="dialogKeys">
      <div class="mb-4 flex size-11 items-center justify-center rounded-full bg-red-100 text-red-600"><svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/></svg></div>
      <h2 id="clear-dialog-title" class="text-lg font-bold text-red-700">确认清空歌单</h2>
      <p id="clear-dialog-description" class="mt-3 text-sm leading-6 text-slate-600">这是危险操作，如果继续则会清空歌单列表。此操作无法撤销。</p>
      <p id="clear-confirmation-hint" class="mt-4 text-sm leading-6 text-slate-600">请输入以下文字后确认：<strong class="mt-1 block select-text font-semibold text-slate-900">{{ confirmation }}</strong></p>
      <form @submit.prevent="clearList">
        <label for="clear-confirmation" class="mt-4 block text-sm font-medium">确认文字</label>
        <input id="clear-confirmation" ref="clearInput" v-model="clearText" :disabled="clearBusy" aria-describedby="clear-confirmation-hint" autocomplete="off" spellcheck="false" maxlength="64" class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900" @keydown.enter="$event.isComposing && $event.preventDefault()" />
        <p v-if="clearError" role="alert" class="mt-3 text-sm text-red-700">{{ clearError }}</p>
        <div class="mt-6 flex justify-end gap-3"><button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 disabled:opacity-50" :disabled="clearBusy" @click="closeClear">取消</button><button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40" :disabled="!canClear">{{ clearBusy ? '清空中…' : '确认清空' }}</button></div>
      </form>
    </section>
  </div>
</template>
