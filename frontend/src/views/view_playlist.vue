<script setup lang="ts">
/** 响应式歌单页面，包含分页、搜索、复制和随机选曲。 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import SongCard from '../components/component_song_card.vue'
import ProfileCard from '../components/component_profile.vue'
import RepositoryInfo from '../components/component_repository.vue'
import { loadBackground } from '../utils/util_background'
import { trackStartup } from '../state/state_loading'
import { messageOf, request } from '../api/api_client'
import { isCancellation } from '../utils/util_error'
import { notify, siteState } from '../state/state_site'
import { isProfile, isSongs, isString, isStrings, type Profile, type Song } from '../types/type_api'

const router = useRouter()
const profile = ref<Profile | null>(null)
const notice = ref('')
const tags = ref<string[]>(['全部'])
const activeTag = ref('全部')
const songs = ref<Song[]>([])
const keyword = ref('')
const busy = ref(false)
const hasMore = ref(true)
const failure = ref('')
const mode = ref<'list' | 'search' | 'random'>('list')
const page = ref(0)
const sentinel = ref<HTMLElement | null>(null)
const list = ref<HTMLElement | null>(null)
const toolsOpen = ref(false)
const footer = ref<HTMLElement | null>(null)
const footerHeight = ref(32)
let observer: IntersectionObserver | undefined
let footerObserver: ResizeObserver | undefined
let controller: AbortController | undefined
let revision = 0
const icp = computed(() => siteState.site.icp.trim())
const securityRecord = computed(() => siteState.site.net.trim())
const recordCode = computed(() => securityRecord.value.replace(/\D/g, ''))
const desktopLayout = computed(() => {
  const cards = [
    { enabled: siteState.theme.desktop_show_profile, area: 'profile', row: 'minmax(0, 40fr)' },
    { enabled: siteState.theme.desktop_show_search, area: 'search', row: 'minmax(44px, 5.5fr)' },
    { enabled: siteState.theme.desktop_show_notice, area: 'notice', row: 'minmax(0, 11fr)' },
    { enabled: siteState.theme.desktop_show_tags, area: 'tags', row: 'minmax(0, 34fr)' },
  ].filter((card) => card.enabled)
  // 仅剩搜索时保留紧凑高度，第二行让歌单继续占满整栏。
  if (cards.length === 1 && cards[0]?.area === 'search') {
    return {
      '--desktop-grid-columns': 'minmax(0, 3.08fr) minmax(245px, 1fr)',
      '--desktop-grid-rows': 'minmax(44px, max-content) minmax(0, 1fr)',
      '--desktop-grid-areas': '"songs search" "songs ."',
    }
  }
  return {
    '--desktop-grid-columns': cards.length ? 'minmax(0, 3.08fr) minmax(245px, 1fr)' : 'minmax(0, 1fr)',
    '--desktop-grid-rows': cards.length ? cards.map((card) => card.row).join(' ') : 'minmax(0, 1fr)',
    '--desktop-grid-areas': cards.length ? cards.map((card) => `"songs ${card.area}"`).join(' ') : '"songs"',
  }
})

/** 用途：取消过期查询并分页加载，防止分类/搜索竞态；入参：是否重置；返回值：无。 */
async function load(reset = false): Promise<void> {
  if (!reset && (busy.value || !hasMore.value || mode.value !== 'list' || failure.value)) return
  if (reset) {
    controller?.abort(); revision++; songs.value = []; page.value = 0; hasMore.value = true; failure.value = ''
  }
  const current = revision
  controller = new AbortController()
  busy.value = true
  try {
    const result = await request('getSongList', isSongs, { tag: activeTag.value, page: page.value + 1 }, controller.signal)
    if (current !== revision) return
    songs.value.push(...result); page.value++; hasMore.value = result.length === 25
  } catch (error: unknown) {
    if (current === revision && !isCancellation(error)) failure.value = messageOf(error)
  } finally {
    if (current === revision) { busy.value = false; await observeEnd() }
  }
}
/**
 * 将分页观察器绑定到当前末尾哨兵。
 * @returns DOM 更新后的绑定任务
 */
async function observeEnd(): Promise<void> {
  await nextTick()
  observer?.disconnect()
  if (sentinel.value && mode.value === 'list' && hasMore.value && !failure.value) observer?.observe(sentinel.value)
}
/**
 * 切换分类并恢复分页列表。
 * @param tag 选择的分类名
 * @returns 无
 */
function selectTag(tag: string): void {
  activeTag.value = tag; keyword.value = ''; mode.value = 'list'; void load(true)
}
/**
 * 搜索输入内容，空输入时随机选曲。
 * @returns 查询完成的任务
 */
async function search(): Promise<void> {
  if (!keyword.value.trim()) { await randomSong(); return }
  controller?.abort(); revision++; const current = revision
  controller = new AbortController(); busy.value = true; failure.value = ''; hasMore.value = false; mode.value = 'search'
  try { const result = await request('search', isSongs, { search: keyword.value.trim() }, controller.signal); if (current === revision) songs.value = result }
  catch (error: unknown) { if (current === revision) failure.value = messageOf(error) }
  finally { if (current === revision) busy.value = false }
}
/**
 * 复制歌曲点歌指令并显示结果。
 * @param song 要复制的歌曲
 * @returns 复制操作完成的任务
 */
async function copy(song: Song): Promise<void> {
  try {
    const text = `点歌 ${song.song}`
    if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(text)
    else {
      const input = document.createElement('textarea'); input.value = text; input.style.position = 'fixed'; input.style.opacity = '0'; document.body.append(input); input.select()
      const copied = document.execCommand('copy'); input.remove(); if (!copied) throw new Error('复制失败，请手动复制点歌指令')
    }
    notify(`已复制：点歌 ${song.song}`)
  } catch (error: unknown) { notify(messageOf(error), 'error') }
}
/**
 * 管理员打开编辑页，访客复制点歌指令。
 * @param song 被点击的歌曲
 * @returns 无
 */
function selectSong(song: Song): void {
  if (siteState.authenticated) void router.push(`/admin/songs/${song.uid}`)
  else void copy(song)
}
/**
 * 读取随机歌曲，手机端同时复制指令。
 * @returns 查询完成的任务
 */
async function randomSong(): Promise<void> {
  controller?.abort(); revision++; const current = revision
  busy.value = true; failure.value = ''; mode.value = 'random'; hasMore.value = false
  try {
    const result = await request('random', isSongs)
    if (current !== revision) return
    songs.value = result
    if (!result.length) notify('歌单暂时没有歌曲')
    else if (window.matchMedia('(max-width: 1023px)').matches) await copy(result[0])
  } catch (error: unknown) { if (current === revision) failure.value = messageOf(error) }
  finally { if (current === revision) busy.value = false }
}
/**
 * 将歌曲滚动区和页面滚动回顶部。
 * @returns 无
 */
function toTop(): void {
  list.value?.scrollTo({ top: 0, behavior: 'smooth' }); window.scrollTo({ top: 0, behavior: 'smooth' }); toolsOpen.value = false
}
const mobileViewport = window.matchMedia('(max-width: 1023px)')
let mounted = false
/**
 * 按挂载状态和视口检查背景是否启用。
 * @returns 是否应请求背景图
 */
function backgroundAllowed(): boolean { return mounted && (mobileViewport.matches ? siteState.theme.mobile_background_image : siteState.theme.desktop_background_image) }
/**
 * 在首屏完成且背景启用时安排懒加载。
 * @returns 无
 */
function prepareBackground(): void {
  if (!document.body.hasAttribute('data-booting') && backgroundAllowed()) void loadBackground(backgroundAllowed)
}
watch(() => [siteState.theme.desktop_background_image, siteState.theme.mobile_background_image], prepareBackground)
onMounted(() => {
  mounted = true
  window.addEventListener('vb:app-ready', prepareBackground, { once: true })
  mobileViewport.addEventListener('change', prepareBackground)
  prepareBackground()
  // 用实际页脚高度为电脑歌单预留空间，兼容设备底部安全区。
  if (footer.value) {
    const measureFooter = (): void => { if (footer.value) footerHeight.value = Math.ceil(footer.value.getBoundingClientRect().height) }
    footerObserver = new ResizeObserver(measureFooter)
    footerObserver.observe(footer.value)
    measureFooter()
  }
  observer = new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) void load() }, { rootMargin: '200px' })
  trackStartup(load(true))
  void request('getTagList', isStrings).then((value) => { tags.value = value }).catch((error: unknown) => notify(messageOf(error), 'error'))
  void request('getNotice', isString).then((value) => { const doc = new DOMParser().parseFromString(value.replace(/<br\s*\/?>/gi, '\n'), 'text/html'); notice.value = doc.body.textContent ?? '' }).catch(() => {})
  void request('vUpInfo', isProfile).then((value) => { profile.value = value }).catch(() => {})
})
onBeforeUnmount(() => {
  mounted = false; window.removeEventListener('vb:app-ready', prepareBackground); mobileViewport.removeEventListener('change', prepareBackground)
  controller?.abort(); observer?.disconnect(); footerObserver?.disconnect(); revision++
})
</script>

<template>
  <div class="playlist-page min-h-dvh" :style="{ '--public-footer-height': `${footerHeight}px` }">
    <main class="playlist-layout" :style="desktopLayout">
      <ProfileCard :profile="profile" :notice="notice" :class="{ 'desktop-card-hidden': !siteState.theme.desktop_show_profile }" />
      <form class="search-area mx-3 min-w-0 py-2 lg:surface lg:mx-0 lg:flex lg:items-center lg:px-2 lg:py-1" :class="{ 'desktop-card-hidden': !siteState.theme.desktop_show_search }" role="search" @submit.prevent="search">
        <div class="search-control">
        <label class="sr-only" for="song-search">搜索歌名、歌手或冠名</label>
        <input id="song-search" v-model="keyword" type="search" maxlength="1200" placeholder="点“搜索”随机，或输入文字搜索" class="search-input" />
        <button type="submit" class="search-submit" :disabled="busy">搜索</button>
        </div>
      </form>
      <section class="notice-area surface scroll-area overflow-auto px-4 py-3 text-xs leading-5 whitespace-pre-wrap text-[var(--all-text)]" :class="{ 'desktop-card-hidden': !siteState.theme.desktop_show_notice }" aria-label="公告">{{ notice || '暂无公告喵～' }}</section>
      <section class="tags-area px-4 pt-2 pb-3 lg:surface lg:flex lg:min-h-0 lg:flex-col lg:px-4 lg:pt-4" :class="{ 'desktop-card-hidden': !siteState.theme.desktop_show_tags }" aria-label="歌曲分类">
        <h2 class="section-title mb-3 hidden lg:block">标签</h2>
        <div class="scroll-area flex flex-wrap gap-1.5 lg:min-h-0 lg:overflow-auto lg:pb-4">
          <button v-for="tag in tags" :key="tag" type="button" class="tag-button" :aria-pressed="mode === 'list' && tag === activeTag" @click="selectTag(tag)">{{ tag }}</button>
        </div>
      </section>
      <section class="songs-area mb-2 flex flex-col px-3 lg:surface lg:mb-0 lg:min-h-0 lg:px-[1.5vw] lg:pt-5 lg:pb-4">
        <div class="mb-3 hidden items-center justify-between lg:flex"><h1 class="section-title">歌单</h1><RouterLink v-if="siteState.authenticated" to="/admin" class="text-xs text-[var(--all-text)]">管理后台</RouterLink></div>
        <div ref="list" class="scroll-area min-h-0 flex-1 lg:overflow-auto lg:pr-2" data-testid="song-scroll">
          <ul class="grid grid-cols-1 gap-2 lg:grid-cols-[repeat(auto-fit,minmax(min(100%,330px),1fr))]" aria-label="歌曲列表">
            <li v-for="song in songs" :key="song.uid"><SongCard :song="song" @select="selectSong" /></li>
          </ul>
          <p v-if="failure" role="alert" class="p-6 text-center text-sm text-[var(--error-text)]">{{ failure }} <button class="underline" @click="failure = ''; mode === 'list' ? load() : search()">重试</button></p>
          <p v-else-if="busy" class="p-5 text-center text-sm text-[var(--all-text)]" aria-live="polite">正在加载…</p>
          <p v-else-if="!songs.length" class="p-8 text-center text-sm text-[var(--all-text)]">{{ mode === 'search' ? '没有找到匹配的歌曲' : '歌单暂时没有歌曲' }}</p>
          <div v-if="mode === 'list' && hasMore && !failure" ref="sentinel" class="h-6" aria-hidden="true"></div>
          <p v-if="!busy && songs.length && (!hasMore || mode !== 'list')" class="py-5 text-center text-xs text-[var(--all-text)]" data-testid="list-end">{{ mode === 'list' ? '已经到底啦' : '点击分类查看完整歌单' }}</p>
        </div>
      </section>
    </main>
    <div class="mobile-tools lg:hidden" @keydown.esc="toolsOpen = false">
      <Transition name="mobile-tools">
        <div v-show="toolsOpen" id="mobile-tool-actions" class="mobile-tool-actions" :inert="!toolsOpen" :aria-hidden="!toolsOpen">
          <button type="button" class="mobile-tool-button" aria-label="移动到顶" @click="toTop">
            <span class="mobile-tool-label" aria-hidden="true">移动到顶</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 20 9-16 9 16Z" /></svg>
          </button>
          <button type="button" class="mobile-tool-button" aria-label="随机选曲" :disabled="busy" @click="randomSong">
            <span class="mobile-tool-label" aria-hidden="true">随机选曲</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 7.07 16.97 2.51-7.39L20 10.07 3 3Z" /><path d="m13 13 6 6" /></svg>
          </button>
          <RouterLink v-if="siteState.authenticated" to="/admin" class="mobile-tool-button" aria-label="管理后台">
            <span class="mobile-tool-label" aria-hidden="true">管理后台</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M9 3v18M3 9h6M3 15h6" /></svg>
          </RouterLink>
        </div>
      </Transition>
      <button type="button" class="mobile-tool-button mobile-tool-toggle" :class="{ 'is-open': toolsOpen }" :aria-label="toolsOpen ? '收起工具' : '展开工具'" :aria-expanded="toolsOpen" aria-controls="mobile-tool-actions" @click="toolsOpen = !toolsOpen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M9 3v18" /></svg>
      </button>
    </div>
    <footer ref="footer" class="public-footer mt-4 w-full bg-[var(--footer-background)] text-center text-xs leading-5 text-[var(--footer-text)] shadow-sm" :class="{ 'mobile-footer-pending': busy || (mode === 'list' && hasMore && !failure) }">
      <div class="footer-line">
        <a :href="`https://space.bilibili.com/${siteState.site.mid}`" target="_blank" rel="noopener noreferrer">@2023 {{ profile?.name || siteState.site.nickname }} 版权所有</a>
        <template v-if="icp">
          <span class="footer-divider" aria-hidden="true">|</span>
          <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">{{ icp }}</a>
        </template>
        <template v-if="securityRecord">
          <span class="footer-divider" aria-hidden="true">|</span>
          <a v-if="recordCode" :href="`https://www.beian.gov.cn/portal/registerSystemInfo?recordcode=${recordCode}`" target="_blank" rel="noopener noreferrer">{{ securityRecord }}</a>
          <span v-else>{{ securityRecord }}</span>
        </template>
        <span class="footer-divider" aria-hidden="true">|</span>
        <RepositoryInfo inline />
      </div>
    </footer>
  </div>
</template>
