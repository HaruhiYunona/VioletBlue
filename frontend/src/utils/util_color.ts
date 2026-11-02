/** 颜色文本解析、HSV 转换及带透明通道的序列化。 */
/** RGB 通道范围为 0–255，透明度范围为 0–1。 */
export interface RgbaColor { r: number; g: number; b: number; a: number }
/** 色相范围为 0–360，饱和度和明度范围为 0–1。 */
export interface HsvColor { h: number; s: number; v: number }
/**
 * 将数值限制在指定区间。
 * @param value 原始值
 * @param min 区间下限，默认 0
 * @param max 区间上限，默认 1
 * @returns 区间内的数值
 */
export const clamp = (value: number, min = 0, max = 1): number => Math.min(max, Math.max(min, value))

/**
 * 解析 HEX、RGB 或 RGBA 文本并保留透明度。
 * @param text 颜色文本
 * @returns RGBA 颜色，格式无效时为 null
 */
export function parseColor(text: string): RgbaColor | null {
  const value = text.trim()
  if (/^#(?:[a-f\d]{3}|[a-f\d]{4}|[a-f\d]{6}|[a-f\d]{8})$/i.test(value)) {
    let hex = value.slice(1)
    if (hex.length <= 4) hex = [...hex].map((part) => part + part).join('')
    return { r: parseInt(hex.slice(0, 2), 16), g: parseInt(hex.slice(2, 4), 16), b: parseInt(hex.slice(4, 6), 16), a: hex.length === 8 ? parseInt(hex.slice(6, 8), 16) / 255 : 1 }
  }
  const parts = value.match(/^rgb(a?)\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0(?:\.\d+)?|1(?:\.0+)?))?\s*\)$/i)
  if (!parts || Boolean(parts[1]) !== (parts[5] !== undefined)) return null
  const [r, g, b] = parts.slice(2, 5).map(Number)
  if ([r, g, b].some((channel) => channel > 255)) return null
  return { r, g, b, a: parts[5] === undefined ? 1 : Number(parts[5]) }
}

/**
 * 将 RGB 通道转换为色板使用的 HSV。
 * @param color 有效 RGBA 颜色
 * @returns 色相、饱和度和明度
 */
export function rgbToHsv(color: RgbaColor): HsvColor {
  const r = color.r / 255; const g = color.g / 255; const b = color.b / 255
  const max = Math.max(r, g, b); const min = Math.min(r, g, b); const delta = max - min
  let h = 0
  if (delta > 0) {
    if (max === r) h = 60 * (((g - b) / delta) % 6)
    else if (max === g) h = 60 * ((b - r) / delta + 2)
    else h = 60 * ((r - g) / delta + 4)
  }
  return { h: (h + 360) % 360, s: max === 0 ? 0 : delta / max, v: max }
}

/**
 * 将色板坐标和透明度转换为 RGB 通道。
 * @param color HSV 坐标
 * @param alpha 0 至 1 的透明度
 * @returns RGBA 颜色
 */
export function hsvToRgb(color: HsvColor, alpha: number): RgbaColor {
  const hue = ((color.h % 360) + 360) % 360 / 60
  const chroma = clamp(color.v) * clamp(color.s)
  const x = chroma * (1 - Math.abs(hue % 2 - 1)); const offset = clamp(color.v) - chroma
  const sectors = [[chroma, x, 0], [x, chroma, 0], [0, chroma, x], [0, x, chroma], [x, 0, chroma], [chroma, 0, x]]
  const [r, g, b] = sectors[Math.floor(hue)]
  return { r: Math.round((r + offset) * 255), g: Math.round((g + offset) * 255), b: Math.round((b + offset) * 255), a: clamp(alpha) }
}

/**
 * 生成透明度保留四位小数的 RGBA 文本。
 * @param color RGBA 颜色
 * @returns rgba(r,g,b,a) 文本
 */
export function rgbaText(color: RgbaColor): string {
  return `rgba(${color.r},${color.g},${color.b},${Math.round(color.a * 10000) / 10000})`
}
/**
 * 生成 HEX 文本，非完全不透明时附加透明通道。
 * @param color RGBA 颜色
 * @returns 大写六位或八位 HEX
 */
export function hexText(color: RgbaColor): string {
  const channels = [color.r, color.g, color.b]
  if (color.a < 1) channels.push(Math.round(color.a * 255))
  return '#' + channels.map((value) => value.toString(16).padStart(2, '0')).join('').toUpperCase()
}
