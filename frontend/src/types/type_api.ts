/** 接口数据结构与运行时类型守卫。 */
export const themeKeys = [
  '--title', '--line-color', '--all-text', '--scroll-bar', '--tag-text', '--tag-background',
  '--song-card', '--song-card-claim', '--type', '--type-selected', '--button-color',
  '--bio-card', '--nickname-color', '--user-data-text', '--tool-box',
  '--page-background', '--surface-background', '--surface-shadow', '--profile-background',
  '--profile-stats-background', '--profile-divider', '--footer-background', '--footer-text',
  '--input-background', '--input-text', '--input-border', '--input-placeholder',
  '--form-input-background', '--form-input-text', '--form-help', '--button-text',
  '--type-text', '--type-selected-text', '--tool-icon-color', '--scroll-track',
  '--focus-ring', '--profile-stats-shadow', '--tool-label-background', '--tool-label-text',
  '--tool-press-shadow', '--error-text', '--toast-background', '--toast-error-background', '--toast-text',
  '--dialog-overlay', '--dialog-background', '--login-start', '--login-end', '--login-background',
  '--login-link', '--upload-background', '--upload-button-background', '--upload-button-text', '--preview-background',
] as const
export const desktopSwitchKeys = ['desktop_show_profile', 'desktop_show_search', 'desktop_show_notice', 'desktop_show_tags'] as const
export type DesktopSwitchKey = typeof desktopSwitchKeys[number]
export type ThemeKey = typeof themeKeys[number]
export type ThemeColors = Record<ThemeKey, string>
export type ThemeDevice = 'desktop' | 'mobile'
export type MobileSongStyle = 'card' | 'compact'
/** PC 和手机的独立配色、背景开关及歌曲布局配置。 */
export type Theme = ThemeColors & Record<DesktopSwitchKey, boolean> & {
  mobile_song_style: MobileSongStyle; mobile_colors: ThemeColors
  desktop_background_image: boolean; mobile_background_image: boolean
}
/** 站点基础资料及主播资料缓存配置。 */
export interface Site {
  title: string; detail: string; icp: string; net: string; mid: number
  nickname: string; level: number; following: number; follower: number; sign: string; api_cache_time: number
}
/** 上游资料和本地回退资料共用的主播结构。 */
export interface Profile { mid: number; name: string; sign: string; face: string; level: number; follower: number; following: number }
/** 歌单列表中的歌曲及分类、冠名。 */
export interface Song { uid: number; song: string; singer: string; type: string[]; notice: string }
/** 歌曲新增和编辑接口使用的表单字段。 */
export interface SongInput { name: string; singer: string; tags: string[]; claim: string }
/** 管理会话状态及写请求令牌。 */
export interface Session { authenticated: boolean; csrf: string }
/** PHP 页面内嵌的站点初始化数据。 */
export interface Boot { site: Site; theme: Theme; authenticated: boolean }

/**
 * 检查值是否为非数组对象。
 * @param value 待校验的数据
 * @returns 是否为键值对象
 */
export function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}
/**
 * 检查值是否为字符串。
 * @param value 待校验的数据
 * @returns 是否为字符串
 */
export const isString = (value: unknown): value is string => typeof value === 'string'
/**
 * 检查值是否为布尔值。
 * @param value 待校验的数据
 * @returns 是否为布尔值
 */
export const isBoolean = (value: unknown): value is boolean => typeof value === 'boolean'
/**
 * 检查值是否为字符串数组。
 * @param value 待校验的数据
 * @returns 是否为字符串列表
 */
export const isStrings = (value: unknown): value is string[] => Array.isArray(value) && value.every(isString)
/**
 * 检查对象指定字段是否均为有限数值。
 * @param record 待校验对象
 * @param keys 数字字段名列表
 * @returns 字段是否全部有效
 */
export const isNumbers = (record: Record<string, unknown>, keys: string[]): boolean => keys.every((key) => typeof record[key] === 'number' && Number.isFinite(record[key]))
/**
 * 校验站点基础配置的字段类型。
 * @param value 接口返回的数据
 * @returns 是否为 Site
 */
export function isSite(value: unknown): value is Site {
  return isRecord(value) && ['title', 'detail', 'icp', 'net', 'nickname', 'sign'].every((key) => isString(value[key])) && isNumbers(value, ['mid', 'level', 'following', 'follower', 'api_cache_time'])
}
/**
 * 校验 PC、手机配色及全部主题开关。
 * @param value 接口返回的数据
 * @returns 是否为 Theme
 */
export function isTheme(value: unknown): value is Theme {
  if (!isRecord(value)) return false
  const mobile = value.mobile_colors
  return isRecord(value) && (value.mobile_song_style === 'card' || value.mobile_song_style === 'compact') && themeKeys.every((key) => isString(value[key])) && desktopSwitchKeys.every((key) => isBoolean(value[key]))
    && isRecord(mobile) && themeKeys.every((key) => isString(mobile[key]))
    && isBoolean(value.desktop_background_image) && isBoolean(value.mobile_background_image)
}
/**
 * 校验统一主播资料结构。
 * @param value 接口返回的数据
 * @returns 是否为 Profile
 */
export function isProfile(value: unknown): value is Profile {
  return isRecord(value) && ['name', 'sign', 'face'].every((key) => isString(value[key])) && isNumbers(value, ['mid', 'level', 'follower', 'following'])
}
/**
 * 校验前端歌曲结构。
 * @param value 接口返回的数据
 * @returns 是否为 Song
 */
export function isSong(value: unknown): value is Song {
  return isRecord(value) && typeof value.uid === 'number' && ['song', 'singer', 'notice'].every((key) => isString(value[key])) && isStrings(value.type)
}
/**
 * 校验歌曲列表的每个元素。
 * @param value 接口返回的数据
 * @returns 是否为 Song 数组
 */
export const isSongs = (value: unknown): value is Song[] => Array.isArray(value) && value.every(isSong)
/**
 * 校验歌曲编辑表单结构。
 * @param value 接口返回的数据
 * @returns 是否为 SongInput
 */
export function isSongInput(value: unknown): value is SongInput {
  return isRecord(value) && ['name', 'singer', 'claim'].every((key) => isString(value[key])) && isStrings(value.tags)
}
/**
 * 校验管理会话响应。
 * @param value 接口返回的数据
 * @returns 是否为 Session
 */
export function isSession(value: unknown): value is Session {
  return isRecord(value) && isBoolean(value.authenticated) && isString(value.csrf)
}
/**
 * 校验 PHP 内嵌的站点初始化数据。
 * @param value 页面内嵌的数据
 * @returns 是否为 Boot
 */
export function isBoot(value: unknown): value is Boot {
  return isRecord(value) && isSite(value.site) && isTheme(value.theme) && isBoolean(value.authenticated)
}
