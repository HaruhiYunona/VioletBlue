<script setup lang="ts">
/** 设备独立配色草稿、调色盘、撤销历史与统一保存。 */
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import ColorPicker from '../components/component_color_picker.vue'
import ThemePreview from '../components/component_theme_preview.vue'
import { request, messageOf } from '../api/api_client'
import { desktopSwitchKeys, isTheme, type DesktopSwitchKey, type ThemeDevice, type ThemeKey } from '../types/type_api'
import { applyTheme, cloneTheme, defaultTheme, notify, siteState, themeColors, themeGroups, themeLabels } from '../state/state_site'
import { DraftHistory } from '../utils/util_history'

const draft = reactive(cloneTheme(siteState.theme))
const busy = ref(false); const error = ref(''); const device = ref<ThemeDevice>('desktop')
const activeColor = ref<ThemeKey | null>(null)
const colors = computed(() => themeColors(draft, device.value))
const visibleGroups = computed(() => themeGroups.map((group) => ({
  // 电脑介绍卡片背景仅显示在 PC 选项卡。
  ...group, keys: group.keys.filter((key) => device.value === 'desktop' || key !== '--profile-background'),
})))
const backgroundEnabled = computed({
  get: () => device.value === 'desktop' ? draft.desktop_background_image : draft.mobile_background_image,
  set: (value: boolean) => { if (device.value === 'desktop') draft.desktop_background_image = value; else draft.mobile_background_image = value },
})
const history = new DraftHistory(draft, cloneTheme)
const revision = ref(0)
const canUndo = computed(() => { void revision.value; return history.canUndo })
const canRedo = computed(() => { void revision.value; return history.canRedo })
const desktopSwitchLabels: Record<DesktopSwitchKey, string> = {
  desktop_show_profile: '显示介绍卡片', desktop_show_search: '显示搜索卡片', desktop_show_notice: '显示公告卡片', desktop_show_tags: '显示分类标签卡片',
}
/**
 * 为当前完整草稿建立去重快照。
 * @returns 无
 */
function checkpoint(): void { history.checkpoint(draft); revision.value++ }
/**
 * 关闭调色盘并记录本次选色。
 * @returns 无
 */
function closePicker(): void { activeColor.value = null; checkpoint() }
/**
 * 切换当前展开的颜色项。
 * @param key 颜色变量名
 * @returns 无
 */
function togglePicker(key: ThemeKey): void { const wasOpen = activeColor.value === key; closePicker(); if (!wasOpen) activeColor.value = key }
/**
 * 保留当前草稿并切换设备选项卡。
 * @param value desktop 或 mobile
 * @returns 无
 */
function switchDevice(value: ThemeDevice): void { closePicker(); device.value = value }
/**
 * 点击色盘和色块之外时关闭色盘。
 * @param event 文档点击事件
 * @returns 无
 */
function outside(event: MouseEvent): void {
  if (activeColor.value && event.target instanceof Element && !event.target.closest('.color-picker, .color-trigger')) closePicker()
}
/**
 * 撤销或重做草稿，先提交当前输入。
 * @param redo 是否重做，默认撤销
 * @returns 无
 */
function travel(redo = false): void {
  if (busy.value) return
  if (document.activeElement instanceof HTMLElement) document.activeElement.blur()
  closePicker()
  const snapshot = redo ? history.redo() : history.undo()
  if (snapshot) Object.assign(draft, snapshot)
  revision.value++
}
/**
 * 处理草稿撤销、重做和关闭快捷键。
 * @param event 键盘事件
 * @returns 无
 */
function keyboard(event: KeyboardEvent): void {
  if (event.key === 'Escape') { closePicker(); return }
  if (!(event.ctrlKey || event.metaKey) || event.altKey) return
  const target = event.target
  // 文本输入保留浏览器原生撤销；其它区域使用整份配色草稿历史。
  if (target instanceof HTMLElement && (target.matches('input:not([type=range]):not([type=checkbox]), textarea') || target.isContentEditable)) return
  if (event.key.toLowerCase() === 'z' || event.key.toLowerCase() === 'y') {
    event.preventDefault(); travel(event.shiftKey || event.key.toLowerCase() === 'y')
  }
}
/**
 * 将草稿恢复为默认主题并保留撤销记录。
 * @returns 无
 */
function reset(): void { closePicker(); Object.assign(draft, cloneTheme(defaultTheme)); checkpoint() }
/**
 * 提交完整主题并应用服务端返回的有效配色。
 * @returns 保存完成的任务
 */
async function save(): Promise<void> {
  if (busy.value) return
  closePicker(); busy.value = true; error.value = ''
  try {
    const theme = await request('changeTheme', isTheme, { colors: draft })
    siteState.theme = cloneTheme(theme); Object.assign(draft, cloneTheme(theme)); applyTheme(theme)
    notify('网站配色已保存，访客刷新后生效')
  } catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
onMounted(() => { document.addEventListener('click', outside); window.addEventListener('keydown', keyboard) })
onBeforeUnmount(() => { document.removeEventListener('click', outside); window.removeEventListener('keydown', keyboard) })
</script>

<template>
  <h1 class="section-title mb-5">网站配色</h1>
  <p class="mb-5 text-sm leading-6 text-[var(--all-text)]">PC 和手机独立配色。点调色盘外部保留当前草稿，最后统一保存；后台始终使用固定配色。</p>
  <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="device-tabs" role="tablist" aria-label="配色设备">
      <button id="theme-desktop-tab" type="button" role="tab" :aria-selected="device === 'desktop'" aria-controls="theme-settings" @click="switchDevice('desktop')">PC 端</button>
      <button id="theme-mobile-tab" type="button" role="tab" :aria-selected="device === 'mobile'" aria-controls="theme-settings" @click="switchDevice('mobile')">手机端</button>
    </div>
    <div class="flex gap-2"><button type="button" class="quiet-button" :disabled="busy || !canUndo" title="Ctrl+Z / ⌘Z" @click="travel()">撤销</button><button type="button" class="quiet-button" :disabled="busy || !canRedo" title="Ctrl+Shift+Z / Ctrl+Y" @click="travel(true)">重做</button></div>
  </div>
  <form class="grid items-start gap-7 md:grid-cols-[minmax(0,1fr)_minmax(240px,36%)]" @submit.prevent="save">
    <fieldset id="theme-settings" :disabled="busy" class="min-w-0" role="tabpanel" :aria-labelledby="`theme-${device}-tab`">
      <label class="mb-6 flex cursor-pointer items-center gap-3 text-sm"><input v-model="backgroundEnabled" type="checkbox" class="size-4 accent-[var(--button-color)]" @change="checkpoint" />显示{{ device === 'desktop' ? 'PC 端' : '手机端' }}背景图片</label>
      <fieldset v-if="device === 'desktop'" class="mb-6 rounded-lg border border-[var(--input-border)] p-4">
        <legend class="px-2 text-sm font-semibold">电脑端右栏卡片</legend>
        <label v-for="key in desktopSwitchKeys" :key="key" class="my-3 flex cursor-pointer items-center gap-3 text-sm">
          <input :id="key" v-model="draft[key]" type="checkbox" class="size-4 accent-[var(--button-color)]" @change="checkpoint" />{{ desktopSwitchLabels[key] }}
        </label>
        <p class="form-help">宽度至少 1024px 时生效，关闭后其余卡片自动调整布局。</p>
      </fieldset>
      <div v-else class="mb-6">
        <label for="mobile-song-style" class="mb-2 block text-sm font-semibold">手机歌曲单元格</label>
        <select id="mobile-song-style" v-model="draft.mobile_song_style" class="w-full rounded-md border border-[var(--input-border)] bg-white px-3 py-2 text-sm" @change="checkpoint"><option value="card">原版卡片（显示歌曲标签）</option><option value="compact">紧凑单行（竖线、歌名－歌手、备注）</option></select>
      </div>
      <fieldset v-for="group in visibleGroups" :key="group.label" class="mb-6 rounded-lg border border-[var(--input-border)] p-4">
        <legend class="px-2 text-sm font-semibold">{{ group.label }}</legend>
        <div v-for="key in group.keys" :key="`${device}-${key}`" class="mb-4 last:mb-0">
          <label :for="`theme-${key}`" class="mb-1.5 block text-sm font-semibold">{{ themeLabels[key] }} <code class="ml-1 text-[10px] font-normal text-[var(--all-text)]">{{ key }}</code></label>
          <div class="flex gap-2">
            <button type="button" :aria-label="`${themeLabels[key]}取色器`" :aria-expanded="activeColor === key" :aria-controls="`picker-${key}`" class="color-trigger" @click="togglePicker(key)"><span :style="{ backgroundColor: colors[key] }"></span></button>
            <input :id="`theme-${key}`" v-model="colors[key]" required maxlength="64" class="min-w-0 flex-1 rounded-md border border-[var(--input-border)] bg-white px-3 font-mono text-sm" @change="checkpoint" />
          </div>
          <ColorPicker v-if="activeColor === key" :id="`picker-${key}`" v-model="colors[key]" :label="themeLabels[key]" :disabled="busy" />
        </div>
      </fieldset>
      <p v-if="error" role="alert" class="my-3 text-sm text-[var(--error-text)]">{{ error }}</p>
      <div class="mt-6 flex flex-wrap gap-3"><button class="action-button" :disabled="busy">{{ busy ? '保存中…' : '保存配色' }}</button><button type="button" class="quiet-button" @click="reset">恢复原版样式与配色</button></div>
      <p class="form-help">支持 HEX、RGB 和 RGBA。Ctrl+Z 撤销，Ctrl+Shift+Z / Ctrl+Y 重做；输入框内使用文本撤销。恢复与撤销均需保存后对访客生效。</p>
    </fieldset>
    <ThemePreview :theme="draft" :device="device" />
  </form>
</template>

<style scoped>
.device-tabs { display: flex; padding: 4px; gap: 4px; border: 1px solid #eee; border-radius: 10px; background: #f7f6fa; }
.device-tabs button { padding: 8px 22px; border-radius: 7px; font-size: 13px; color: #777; cursor: pointer; }
.device-tabs button[aria-selected=true] { background: #9370db; color: #fff; box-shadow: 0 2px 5px #9370db25; }
.color-trigger { position: relative; flex-shrink: 0; width: 48px; height: 40px; overflow: hidden; border: 1px solid #ddd; border-radius: 7px; background: conic-gradient(#e5e7eb 25%, #fff 0 50%, #e5e7eb 0 75%, #fff 0) 0 0 / 10px 10px; }
.color-trigger span { position: absolute; inset: 3px; border-radius: 3px; }
</style>
