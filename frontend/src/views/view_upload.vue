<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { request, messageOf } from '../api/api_client'
import { isString } from '../types/type_api'
import { notify } from '../state/state_site'
import { beginLoading } from '../state/state_loading'
const route = useRoute(); const file = ref<File>(); const busy = ref(false); const error = ref('')
const target = computed(() => typeof route.params.type === 'string' ? route.params.type : '')
const labels: Record<string, string> = { face: '头像', background: '背景图片', favicon: '网站图标', ifont: '字体' }
/**
 * 从文件输入读取当前选择。
 * @param event 文件选择事件
 * @returns 无
 */
function choose(event: Event): void { if (event.target instanceof HTMLInputElement) file.value = event.target.files?.[0] }
/**
 * 读取文件为数据 URI 并提交上传。
 * @returns 上传完成的任务
 */
async function upload(): Promise<void> {
  if (!file.value) { error.value = '请先选择文件'; return }
  if (!file.value.size || file.value.size > 2 * 1024 * 1024) { error.value = '文件不能为空，且最大为 2MiB'; return }
  busy.value = true; error.value = ''
  const endLoading = beginLoading('正在读取并上传文件…')
  try {
    const content = await new Promise<string>((resolve, reject) => { const reader = new FileReader(); reader.onload = () => typeof reader.result === 'string' ? resolve(reader.result) : reject(new Error('文件读取失败')); reader.onerror = () => reject(new Error('文件读取失败')); if (file.value) reader.readAsDataURL(file.value) })
    const message = await request('uploadFile', isString, { type: target.value, file: content }); notify(message)
  } catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false; endLoading() }
}
</script>
<template>
  <h1 class="section-title mb-6">上传{{ labels[target] || '文件' }}</h1>
  <form v-if="labels[target]" @submit.prevent="upload"><label for="upload-file" class="mt-8 flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-lg bg-[var(--upload-background)] px-6 text-center"><span class="rounded bg-[var(--upload-button-background)] px-5 py-3 text-lg font-bold text-[var(--upload-button-text)]">↑ 点击选择文件</span><span class="mt-4 max-w-full break-all text-sm">{{ file?.name || (target === 'ifont' ? 'TTF 字体，最大 2MiB' : 'PNG / JPEG / GIF / WebP / ICO，最大 2MiB') }}</span><input id="upload-file" type="file" :accept="target === 'ifont' ? '.ttf,font/ttf' : 'image/png,image/jpeg,image/gif,image/webp,image/x-icon,image/vnd.microsoft.icon'" class="sr-only" @change="choose" /></label><p v-if="error" role="alert" class="mt-5 text-sm text-[var(--error-text)]">{{ error }}</p><button class="action-button mt-7" :disabled="busy || !file">{{ busy ? '上传中…' : '提交文件' }}</button></form>
  <p v-else role="alert">不支持的上传类型。</p><RouterLink to="/admin/settings" class="mt-6 inline-block text-sm underline">返回基础设置</RouterLink>
</template>
