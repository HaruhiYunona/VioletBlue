<script setup lang="ts">
/** 后台布局、导航和退出入口。 */
import { RouterLink, RouterView, useRouter } from 'vue-router'
import RepositoryInfo from '../components/component_repository.vue'
import { request, messageOf } from '../api/api_client'
import { isBoolean } from '../types/type_api'
import { notify, siteState } from '../state/state_site'
const router = useRouter()
/**
 * 退出管理会话并跳转登录页。
 * @returns 退出完成的任务
 */
async function logout(): Promise<void> {
  try { await request('logout', isBoolean); siteState.authenticated = false; await router.replace('/admin/login') }
  catch (error: unknown) { notify(messageOf(error), 'error') }
}
</script>
<template>
  <div class="admin-background">
    <div class="mx-auto min-h-dvh w-[90%] max-w-[800px] py-6 pb-14">
      <header class="mb-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        <RouterLink to="/admin" class="font-profile font-bold text-[var(--title)]">管理后台</RouterLink>
        <nav class="flex gap-4"><RouterLink to="/" class="text-[var(--all-text)]">歌单</RouterLink><RouterLink to="/admin/theme" class="text-[var(--all-text)]">配色</RouterLink><button class="text-[var(--all-text)]" @click="logout">退出登录</button></nav>
      </header>
      <RouterView />
      <footer class="mt-14 flex items-center justify-center text-center text-xs leading-5"><RepositoryInfo /></footer>
    </div>
  </div>
</template>
