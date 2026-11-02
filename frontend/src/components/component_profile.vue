<script setup lang="ts">
/** 主播资料卡片，按设备切换布局并提供本地头像回退。 */
import { computed } from 'vue'
import { siteState } from '../state/state_site'
import type { Profile } from '../types/type_api'
const props = defineProps<{ profile: Profile | null; notice: string }>()
/**
 * 远程头像加载失败时切换到本地头像。
 * @param event 图片错误事件
 * @returns 无
 */
function imageError(event: Event): void {
  if (event.target instanceof HTMLImageElement && !event.target.src.endsWith('/static/img/face.png')) event.target.src = '/static/img/face.png'
}
const values = computed(() => [
  { label: '等级', value: props.profile?.level ?? siteState.site.level },
  { label: '关注', value: props.profile?.following ?? siteState.site.following },
  { label: '粉丝', value: props.profile?.follower ?? siteState.site.follower },
])
</script>
<template>
  <section class="profile-area mb-10 min-w-0 bg-[var(--bio-card)] text-center lg:mb-0 lg:overflow-auto lg:bg-[var(--profile-background)] lg:shadow-[1px_1px_5px_var(--surface-shadow)]">
    <h2 class="section-title m-4 hidden text-left lg:block">介绍</h2>
    <div class="flex min-h-[270px] flex-col items-center px-5 pt-11 lg:min-h-0 lg:px-4 lg:pt-0">
      <img :src="profile?.face || '/static/img/face.png'" alt="主播头像" referrerpolicy="no-referrer" class="size-[100px] rounded-full object-cover lg:size-[clamp(64px,12dvh,128px)]" @error="imageError" />
      <a :href="`https://space.bilibili.com/${siteState.site.mid}`" target="_blank" rel="noopener noreferrer" class="font-profile mt-2 max-w-full truncate text-2xl font-bold text-[var(--nickname-color)] lg:text-[clamp(16px,1.2vw,22px)] lg:text-[var(--title)]">{{ profile?.name || siteState.site.nickname }}</a>
      <p class="mt-3 line-clamp-3 max-w-full text-xs leading-5 text-[var(--all-text)] lg:hidden">{{ notice }}</p>
    </div>
    <dl class="font-profile relative mx-2 -mb-10 grid h-[70px] grid-cols-3 rounded-2xl bg-[var(--profile-stats-background)] py-2 text-[var(--user-data-text)] shadow-[0_3px_15px_var(--profile-stats-shadow)] lg:mx-4 lg:mt-2 lg:mb-2 lg:h-auto lg:rounded-none lg:bg-transparent lg:py-0 lg:text-[var(--title)] lg:shadow-none">
      <div v-for="item in values" :key="item.label" class="border-r border-[var(--profile-divider)] last:border-0">
        <dt class="text-base font-bold lg:text-sm">{{ item.label }}</dt><dd class="mt-1 text-base font-bold lg:text-sm">{{ item.value.toLocaleString() }}</dd>
      </div>
    </dl>
    <p class="mx-4 mt-3 hidden text-left text-xs leading-5 text-[var(--all-text)] lg:line-clamp-3">{{ profile?.sign ?? siteState.site.sign }}</p>
  </section>
</template>
