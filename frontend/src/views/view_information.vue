<script setup lang="ts">
/** 项目联系方式与许可证展示。 */
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import license from '../../../LICENSE?raw'
const route = useRoute(); const agreement = computed(() => route.params.page === 'agreement')
const contacts = [
  ['联系邮箱：contact@example.com', 'mailto:contact@example.com'],
  ['Github@HaruhiYunona', 'https://github.com/HaruhiYunona'],
  ['X：@Neko_Kuyo', 'https://x.com/Neko_Kuyo'],
]
const rules = [
  '本网站源代码为开源免费使用,保证无违规内容。您可自行改动网站代码文件,但不得发布有违公序良俗和法律有关内容。',
  '用户自行改动代码、网站内容不代表作者意见,网站内容与作者无关，请自行为自己的行为负责。',
  '严格遵守开源协议, 执行开源协议内容。',
  '版权归属本作者HaruhiYunona所有,禁止直接盗版。如要进行改版开源请至代码仓库fork项目(包括为主播定制)。',
  '有意见请至代码仓库提出issue或发送邮件,请勿随意抹黑造谣本项目内容。',
  '如您有意协助本项目开发,请用邮件联系本作者,经审核后会为您添加权限及作者声明。',
  '本作者保留本代码项目一切合法权益。',
]
</script>
<template>
  <h1 class="section-title mb-8">{{ agreement ? '用户协议' : '联系作者' }}</h1>
  <template v-if="agreement"><span class="song-tag">MIT LICENSE</span><pre class="mt-5 max-w-full font-sans text-sm leading-6 whitespace-pre-wrap break-words">{{ license }}</pre><h2 class="mt-8 mb-4 font-bold">用户公约</h2><ol class="list-decimal space-y-4 pl-5 text-sm leading-6"><li v-for="rule in rules" :key="rule">{{ rule }}</li></ol></template>
  <div v-else class="space-y-6"><a v-for="contact in contacts" :key="contact[1]" :href="contact[1]" target="_blank" rel="noopener noreferrer" class="block break-all text-lg">{{ contact[0] }}</a><p class="text-sm text-[var(--all-text)]">请将示例邮箱替换为站点联系方式。</p></div>
</template>
