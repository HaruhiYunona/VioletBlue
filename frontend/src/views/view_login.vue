<script setup lang="ts">
/** 管理员密码登录页面。 */
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { isBoolean } from '../types/type_api'
import { messageOf, request } from '../api/api_client'
import { notify, siteState } from '../state/state_site'
const password = ref('')
const busy = ref(false)
const error = ref('')
const router = useRouter(); const route = useRoute()
/**
 * 提交密码，登录成功后跳转有效后台目标。
 * @returns 登录完成的任务
 */
async function login(): Promise<void> {
  if (busy.value) return
  busy.value = true; error.value = ''
  try {
    await request('passport', isBoolean, { passWd: password.value }); siteState.authenticated = true; password.value = ''; notify('登录成功')
    const next = route.query.next; await router.replace(typeof next === 'string' && next.startsWith('/admin') && !next.startsWith('//') ? next : '/admin')
  } catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
</script>
<template>
  <main class="flex min-h-dvh items-center justify-center bg-gradient-to-r from-[var(--login-start)] to-[var(--login-end)] px-5 py-12">
    <form class="flex min-h-[500px] w-full max-w-[350px] flex-col rounded-2xl bg-[var(--login-background)] px-5 pt-12 pb-8 text-center" @submit.prevent="login">
      <h1 class="mt-2 mb-10 text-3xl font-bold">登录</h1>
      <label class="sr-only" for="admin-password">管理员密码</label>
      <input id="admin-password" v-model="password" type="password" minlength="6" maxlength="1024" autocomplete="current-password" placeholder="密码" required class="mx-auto mt-5 w-[90%] border-0 border-b border-[var(--login-start)] bg-[var(--input-background)] text-[var(--input-text)] placeholder:text-[var(--input-placeholder)] px-3 py-3 outline-none" />
      <p v-if="error" role="alert" class="mt-4 text-sm text-[var(--error-text)]">{{ error }}</p>
      <button class="mx-auto mt-12 w-[90%] rounded-xl bg-gradient-to-r from-[var(--login-start)] to-[var(--login-end)] py-2.5 font-bold tracking-[10px] text-[var(--button-text)]" :disabled="busy">{{ busy ? '登录中' : '确定' }}</button>
      <RouterLink to="/" class="mt-auto pt-12 text-sm text-[var(--login-link)]">返回歌单</RouterLink>
    </form>
  </main>
</template>
