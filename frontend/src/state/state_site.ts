/** 站点状态、默认配色和初始化数据，以及后台固定配色。 */
import { reactive } from 'vue'
import { messageOf, request } from '../api/api_client'
import { isBoot, isRecord, isSession, isSite, isTheme, themeKeys, type Site, type Theme, type ThemeColors, type ThemeDevice, type ThemeKey } from '../types/type_api'

declare global { interface Window { __VB_BOOTSTRAP__?: unknown } }

const defaultSite: Site = { title: 'VioletBlue 歌单', detail: '欢迎来到我的歌单站', icp: '', net: '', mid: 0, nickname: 'VioletBlue', level: 1, following: 1, follower: 1, sign: 'NO Bio yet.', api_cache_time: 3600 }
export const defaultColors: ThemeColors = {
  '--title': '#000000', '--line-color': '#8a2be2', '--all-text': '#808080', '--scroll-bar': 'rgba(138,43,226,0.2)',
  '--tag-text': '#562385', '--tag-background': 'rgba(138,43,226,0.2)', '--song-card': 'rgba(249,216,255,0.25)',
  '--song-card-claim': 'rgba(249,216,255,0.7)', '--type': '#d8bfd8', '--type-selected': '#9370db', '--button-color': '#9370db',
  '--bio-card': 'rgba(138,43,226,0.2)', '--nickname-color': '#9370db', '--user-data-text': '#594581', '--tool-box': '#c9b3f4',
  '--page-background': '#ffffff', '--surface-background': 'rgba(255,255,255,0.9)',
  '--surface-shadow': '#dcdcdc', '--profile-background': 'rgba(255,255,255,0.9)',
  '--profile-stats-background': '#ffffff', '--profile-divider': '#e5e7eb',
  '--footer-background': 'rgba(255,255,255,0.9)', '--footer-text': '#6b7280',
  '--input-background': '#ffffff', '--input-text': '#000000',
  '--input-border': '#ddd6fe', '--input-placeholder': '#9ca3af',
  '--form-input-background': '#b39fdb', '--form-input-text': '#ffffff',
  '--form-help': '#7b68ee', '--button-text': '#ffffff',
  '--type-text': '#000000', '--type-selected-text': '#ffffff',
  '--tool-icon-color': '#ffffff', '--scroll-track': '#ededed',
  '--focus-ring': '#a78bfa', '--profile-stats-shadow': 'rgba(0,0,0,0.1)',
  '--tool-label-background': 'rgba(0,0,0,0.3)', '--tool-label-text': '#ffffff',
  '--tool-press-shadow': 'rgba(0,0,0,0.15)', '--error-text': '#dc2626',
  '--toast-background': '#7c3aed', '--toast-error-background': '#dc2626', '--toast-text': '#ffffff',
  '--dialog-overlay': 'rgba(0,0,0,0.4)', '--dialog-background': '#ffffff',
  '--login-start': '#fbc2eb', '--login-end': '#a6c1ee', '--login-background': '#ffffff',
  '--login-link': '#9370db', '--upload-background': '#e9967a',
  '--upload-button-background': '#d3394c', '--upload-button-text': '#f1e5e6', '--preview-background': '#ffffff',
}
export const defaultTheme: Theme = {
  ...defaultColors, mobile_colors: { ...defaultColors }, mobile_song_style: 'card',
  desktop_show_profile: true, desktop_show_search: true, desktop_show_notice: true, desktop_show_tags: true,
  desktop_background_image: true, mobile_background_image: true,
}
/**
 * 复制主题及手机配色映射，隔离编辑草稿。
 * @param theme 原始主题
 * @returns 独立主题副本
 */
export function cloneTheme(theme: Theme): Theme { return { ...theme, mobile_colors: { ...theme.mobile_colors } } }
/**
 * 选择对应设备的颜色映射。
 * @param theme 完整主题
 * @param device desktop 或 mobile
 * @returns 该设备的配色映射
 */
export function themeColors(theme: Theme, device: ThemeDevice): ThemeColors { return device === 'mobile' ? theme.mobile_colors : theme }
const boot = isBoot(window.__VB_BOOTSTRAP__) ? window.__VB_BOOTSTRAP__ : undefined
// 后台始终使用原版固定配色，独立于访客主题及未保存的前台预览。
export const adminColors = Object.freeze(themeKeys.reduce<Record<string, string>>((colors, key) => {
  colors[key] = defaultTheme[key]
  return colors
}, {}))
export const siteState = reactive({ site: boot?.site ?? defaultSite, theme: boot?.theme ?? cloneTheme(defaultTheme), authenticated: boot?.authenticated ?? false, ready: Boolean(boot), error: '' })
export const toast = reactive({ message: '', kind: 'success' })
let timer: ReturnType<typeof setTimeout> | undefined

/**
 * 显示消息并重置自动隐藏计时。
 * @param message 提示文字
 * @param kind success 或 error，默认 success
 * @returns 无
 */
export function notify(message: string, kind = 'success'): void {
  toast.message = message; toast.kind = kind
  clearTimeout(timer)
  timer = setTimeout(() => { toast.message = '' }, 4000)
}

/**
 * 按视口应用配色及背景开关。
 * @param theme 完整主题
 * @returns 无
 */
export function applyTheme(theme: Theme): void {
  const mobile = window.matchMedia('(max-width: 1023px)').matches
  const colors = themeColors(theme, mobile ? 'mobile' : 'desktop')
  for (const key of themeKeys) document.documentElement.style.setProperty(key, colors[key])
  document.documentElement.style.setProperty('--site-background-image', (mobile ? theme.mobile_background_image : theme.desktop_background_image) ? 'var(--loaded-background-image, none)' : 'none')
}
/**
 * 更新站点状态、页面标题和描述。
 * @param site 站点基础配置
 * @returns 无
 */
export function applySite(site: Site): void {
  siteState.site = site
  document.title = site.title
  document.querySelector('meta[name="description"]')?.setAttribute('content', site.detail)
}
/**
 * 重新请求并应用站点基础配置。
 * @returns 更新完成的任务
 */
export async function refreshSite(): Promise<void> {
  applySite(await request('siteInfo', isSite))
}
export const themeLabels: Record<ThemeKey, string> = {
  '--title': '标题文字', '--line-color': '标题与歌曲装饰线', '--all-text': '正文与公告', '--scroll-bar': '滚动条',
  '--tag-text': '歌曲标签文字', '--tag-background': '歌曲标签背景', '--song-card': '歌曲卡片', '--song-card-claim': '有冠名的歌曲卡片',
  '--type': '分类标签', '--type-selected': '选中分类', '--button-color': '按钮', '--bio-card': '手机端资料背景',
  '--nickname-color': '手机端昵称', '--user-data-text': '手机端统计文字', '--tool-box': '悬浮工具按钮',
  '--page-background': '页面底色', '--surface-background': '通用面板背景',
  '--surface-shadow': '面板阴影', '--profile-background': '电脑端介绍卡片背景',
  '--profile-stats-background': '手机端统计卡片背景', '--profile-divider': '资料统计分隔线',
  '--footer-background': '页脚背景', '--footer-text': '页脚文字',
  '--input-background': '搜索框背景', '--input-text': '搜索框文字',
  '--input-border': '配色输入框边框', '--input-placeholder': '输入框提示文字',
  '--form-input-background': '后台表单输入框背景', '--form-input-text': '后台表单输入框文字',
  '--form-help': '后台表单帮助文字', '--button-text': '主要按钮文字',
  '--type-text': '分类标签文字', '--type-selected-text': '选中分类文字',
  '--tool-icon-color': '悬浮工具图标颜色', '--scroll-track': '滚动条轨道背景',
  '--focus-ring': '歌曲键盘焦点边框', '--profile-stats-shadow': '手机统计卡片阴影',
  '--tool-label-background': '悬浮工具提示背景', '--tool-label-text': '悬浮工具提示文字',
  '--tool-press-shadow': '悬浮工具按下阴影', '--error-text': '错误提示文字',
  '--toast-background': '消息提示背景', '--toast-error-background': '错误消息背景', '--toast-text': '消息提示文字',
  '--dialog-overlay': '弹窗遮罩', '--dialog-background': '加载失败提示面板背景',
  '--login-start': '登录渐变起始色与输入边框', '--login-end': '登录渐变结束色', '--login-background': '登录卡片背景',
  '--login-link': '登录返回链接', '--upload-background': '上传区域背景',
  '--upload-button-background': '选择文件按钮背景', '--upload-button-text': '选择文件按钮文字', '--preview-background': '配色预览面板背景',
}
export const themeGroups: readonly { label: string; keys: readonly ThemeKey[] }[] = [
  { label: '页面与面板', keys: ['--title', '--line-color', '--all-text', '--page-background', '--surface-background', '--surface-shadow', '--scroll-bar', '--scroll-track', '--focus-ring'] },
  { label: '歌单、分类与按钮', keys: ['--song-card', '--song-card-claim', '--tag-text', '--tag-background', '--type', '--type-text', '--type-selected', '--type-selected-text', '--button-color', '--button-text'] },
  { label: '资料与统计', keys: ['--bio-card', '--nickname-color', '--user-data-text', '--profile-background', '--profile-stats-background', '--profile-divider', '--profile-stats-shadow'] },
  { label: '搜索栏', keys: ['--input-background', '--input-text', '--input-placeholder'] },
  { label: '手机悬浮工具', keys: ['--tool-box', '--tool-icon-color', '--tool-label-background', '--tool-label-text', '--tool-press-shadow'] },
  { label: '页脚与消息提示', keys: ['--footer-background', '--footer-text', '--error-text', '--toast-background', '--toast-error-background', '--toast-text', '--dialog-background'] },
]
let pending: Promise<void> | undefined
/**
 * 复用内嵌配置或并行读取站点、主题和会话。
 * @returns 初始化完成的任务
 */
export function initialize(): Promise<void> {
  if (siteState.ready) { applyTheme(siteState.theme); applySite(siteState.site); return Promise.resolve() }
  return pending ??= Promise.all([request('siteInfo', isSite), request('themeInfo', isTheme), request('sessionInfo', isSession)])
    .then(([site, theme, session]) => { siteState.theme = theme; siteState.authenticated = session.authenticated; applyTheme(theme); applySite(site); siteState.ready = true })
    .catch((error: unknown) => { siteState.error = messageOf(error); throw error })
}

window.matchMedia('(max-width: 1023px)').addEventListener('change', () => applyTheme(siteState.theme))
if (new URLSearchParams(location.search).has('theme-preview') && window.parent !== window) {
  // 预览只接收同源父窗口的完整主题，改动保留在 iframe 状态内。
  window.addEventListener('message', (event: MessageEvent<unknown>) => {
    if (event.origin !== location.origin || event.source !== window.parent || !isRecord(event.data) || event.data.type !== 'vb-theme-preview' || !isTheme(event.data.theme)) return
    siteState.theme = cloneTheme(event.data.theme)
    applyTheme(siteState.theme)
  })
}
