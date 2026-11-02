<script setup lang="ts">
/** 将实际歌单页面缩放为设备预览，通过同源消息同步草稿。 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { cloneTheme } from '../state/state_site'
import { isRecord, type Theme, type ThemeDevice } from '../types/type_api'

const props = defineProps<{ theme: Theme; device: ThemeDevice }>()
const frame = ref<HTMLIFrameElement | null>(null)
const container = ref<HTMLElement | null>(null)
const width = ref(300)
const viewport = computed(() => props.device === 'desktop' ? 1280 : 390)
const scale = computed(() => Math.min(1, width.value / viewport.value))
let observer: ResizeObserver | undefined
/**
 * 向同源预览页面发送主题副本。
 * @returns 无
 */
function send(): void {
  // 展开响应式映射为普通对象后发送，预览不共享编辑器内存。
  frame.value?.contentWindow?.postMessage({ type: 'vb-theme-preview', theme: cloneTheme(props.theme) }, location.origin)
}
/**
 * 校验预览页消息来源并同步最新草稿。
 * @param event 预览 iframe 的消息事件
 * @returns 无
 */
function ready(event: MessageEvent<unknown>): void {
  if (event.origin === location.origin && event.source === frame.value?.contentWindow && isRecord(event.data) && event.data.type === 'vb-theme-preview-ready') send()
}
watch(() => props.theme, send, { deep: true, flush: 'post' })
onMounted(() => {
  window.addEventListener('message', ready)
  observer = new ResizeObserver((entries) => { const entry = entries[0]; if (entry) width.value = entry.contentRect.width })
  if (container.value) observer.observe(container.value)
})
onBeforeUnmount(() => { observer?.disconnect(); window.removeEventListener('message', ready) })
</script>

<template>
  <aside class="self-start rounded-xl border border-[#eee] bg-white p-3 md:sticky md:top-5" data-testid="theme-preview">
    <h2 class="mb-3 text-sm font-semibold">{{ device === 'desktop' ? 'PC 端' : '手机端' }}实时预览</h2>
    <div ref="container" class="relative overflow-hidden rounded-lg bg-[#f4f4f6]" :style="{ height: `${900 * scale}px` }">
      <iframe ref="frame" src="/?theme-preview=1#/" :title="`${device === 'desktop' ? 'PC' : '手机'}配色预览`" tabindex="-1" class="pointer-events-none absolute left-0 top-0 origin-top-left border-0" :style="{ width: `${viewport}px`, height: '900px', transform: `scale(${scale})` }" @load="send"></iframe>
    </div>
    <p class="mt-3 text-xs leading-5 text-[#888]">预览使用实际页面；修改仅保留在草稿，点击保存后生效。</p>
  </aside>
</template>
