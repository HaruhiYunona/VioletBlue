<script setup lang="ts">
/** 应用外壳：隔离后台配色，展示页面、消息和操作遮罩。 */
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { adminColors, siteState, toast } from './state/state_site'
import { loadingState } from './state/state_loading'
const route = useRoute()
const isAdmin = computed(() => route.path.startsWith('/admin'))
/**
 * 重新载入页面以重试站点初始化。
 * @returns 无
 */
function reload(): void { window.location.reload() }
</script>

<template>
  <div :class="{ 'admin-palette': isAdmin }" :style="isAdmin ? adminColors : undefined" :inert="loadingState.visible">
  <main v-if="siteState.error" class="mx-auto mt-20 max-w-lg rounded-xl bg-[var(--dialog-background)] p-8 shadow-lg">
    <h1 class="text-xl font-bold">暂时无法加载歌单</h1>
    <p class="my-4 text-[var(--all-text)]">{{ siteState.error }}</p>
    <a href="/" class="action-button">打开安装页面</a>
    <button class="ml-4 underline" @click="reload">重新加载</button>
  </main>
  <RouterView v-else />
  <Transition name="toast">
    <div v-if="toast.message" role="status" aria-live="polite" class="fixed top-5 left-1/2 z-50 w-max max-w-[90vw] -translate-x-1/2 rounded-lg px-6 py-3 text-sm text-[var(--toast-text)] shadow-lg" :class="toast.kind === 'error' ? 'bg-[var(--toast-error-background)]' : 'bg-[var(--toast-background)]'">{{ toast.message }}</div>
  </Transition>
  </div>
  <Transition name="operation">
    <div v-if="loadingState.visible" class="operation-overlay" role="status" :aria-hidden="!loadingState.visible" aria-live="polite" data-testid="operation-loading"><div class="operation-card"><span class="loading-orbit" aria-hidden="true"></span><p>{{ loadingState.label }}</p></div></div>
  </Transition>
</template>
