<script setup lang="ts">
/** 站点基础资料和静态资源上传入口。 */
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { messageOf, request } from '../api/api_client'
import { isBoolean, type Site } from '../types/type_api'
import { notify, refreshSite, siteState } from '../state/state_site'
const form = reactive({ ...siteState.site }); const busy = ref(false); const error = ref('')
const fields: { key: keyof Site; name: string; constant: string; numeric?: boolean; help?: string }[] = [
  { key: 'title', name: '网站名称', constant: 'SITE_TITLE' }, { key: 'detail', name: '网站描述', constant: 'SITE_DETAIL' },
  { key: 'mid', name: 'Vup Mid', constant: 'VUP_MID', numeric: true, help: 'B站空间中的用户 MID。填 0 时使用下方本地资料与上传的头像。' },
  { key: 'nickname', name: '本地昵称', constant: 'VTB_NICKNAME' }, { key: 'level', name: '本地等级', constant: 'VTB_LEVEL', numeric: true },
  { key: 'following', name: '本地关注数量', constant: 'VTB_FOLLOWING', numeric: true }, { key: 'follower', name: '本地粉丝数量', constant: 'VTB_FOLLOWER', numeric: true },
  { key: 'sign', name: '本地个人签名', constant: 'VTB_SIGN' }, { key: 'icp', name: 'ICP备案号', constant: 'ICP_FILINGS' }, { key: 'net', name: '网安备案号', constant: 'NETWORK_FILINGS' },
  { key: 'api_cache_time', name: '主播资料缓存（秒）', constant: 'API_CACHE_TIME', numeric: true, help: '沿用原 API 缓存时间配置，控制 B站资料更新频率，最长一天；0 表示每次重新获取。' },
]
/**
 * 按字段类型将输入写入基础配置草稿。
 * @param key 站点字段名
 * @param event 输入事件
 * @returns 无
 */
function setField(key: keyof Site, event: Event): void {
  if (!(event.target instanceof HTMLInputElement)) return
  const value = event.target.value
  switch (key) {
    case 'mid': case 'level': case 'following': case 'follower': case 'api_cache_time': form[key] = value === '' ? 0 : Number(value); break
    default: form[key] = value
  }
}
/**
 * 提交基础配置并刷新站点状态。
 * @returns 保存完成的任务
 */
async function save(): Promise<void> {
  error.value = ''; busy.value = true
  try { await request('changeOption', isBoolean, { config: fields.map((field) => ({ name: field.constant, new: form[field.key] })) }); await refreshSite(); notify('基础设置已保存') }
  catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
</script>
<template>
  <h1 class="section-title mb-6">基础设置</h1>
  <form @submit.prevent="save"><div v-for="field in fields" :key="field.key"><label :for="`setting-${field.key}`" class="form-label">{{ field.name }}</label><input :id="`setting-${field.key}`" :value="form[field.key]" :type="field.numeric ? 'number' : 'text'" :min="field.numeric ? 0 : undefined" :max="field.numeric ? Number.MAX_SAFE_INTEGER : undefined" :maxlength="field.numeric ? undefined : 5000" :required="field.key === 'title'" class="form-input" @input="setField(field.key, $event)" /><p v-if="field.help" class="form-help">{{ field.help }}</p></div>
    <p v-if="error" role="alert" class="mt-5 text-sm text-[var(--error-text)]">{{ error }}</p><button class="action-button mt-7" :disabled="busy">{{ busy ? '保存中…' : '保存设置' }}</button>
  </form>
  <div class="mt-8 grid gap-3 sm:grid-cols-2"><RouterLink v-for="asset in [['face', '更改头像'], ['favicon', '更改网站图标'], ['background', '更改背景'], ['ifont', '修改字体']]" :key="asset[0]" :to="`/admin/upload/${asset[0]}`" class="quiet-button justify-center">{{ asset[1] }}</RouterLink></div>
</template>
