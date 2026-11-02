<script setup lang="ts">
/** RGBA 调色盘：选色、透明度、文本联动和键盘操作。 */
import { computed, reactive, ref, watch } from 'vue'
import { clamp, hexText, hsvToRgb, parseColor, rgbaText, rgbToHsv, type RgbaColor } from '../utils/util_color'

const props = defineProps<{ modelValue: string; label: string; id: string; disabled?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const initial = parseColor(props.modelValue) ?? { r: 147, g: 112, b: 219, a: 1 }
const hsv = reactive(rgbToHsv(initial)); const alpha = ref(initial.a)
const color = computed(() => hsvToRgb(hsv, alpha.value))
const rgba = ref(rgbaText(initial)); const hex = ref(hexText(initial)); const error = ref('')
const rgbaField = ref<HTMLInputElement | null>(null); const hexField = ref<HTMLInputElement | null>(null)
const percentage = computed(() => Math.round(alpha.value * 1000) / 10)
const opaque = computed(() => `rgb(${color.value.r},${color.value.g},${color.value.b})`)
let pointer: number | null = null

/**
 * 将当前颜色同步到文本字段并清除校验状态。
 * @returns 无
 */
function syncFields(): void {
  rgba.value = rgbaText(color.value); hex.value = hexText(color.value); error.value = ''
  rgbaField.value?.setCustomValidity(''); hexField.value?.setCustomValidity('')
}
/**
 * 将 RGBA 同步到 HSV 坐标、透明度和文本。
 * @param value 待加载的 RGBA 颜色
 * @returns 无
 */
function loadColor(value: RgbaColor): void {
  const converted = rgbToHsv(value)
  if (converted.s > 0) hsv.h = converted.h
  hsv.s = converted.s; hsv.v = converted.v; alpha.value = value.a
  syncFields()
}
watch(() => props.modelValue, (value) => {
  const parsed = parseColor(value)
  if (!parsed) return
  const current = color.value
  // 自身拖动回传时保留 HSV 坐标，避免灰色/黑色和 RGB 舍入使拖点跳动。
  if (rgbaText(parsed) === rgbaText(current)) return
  loadColor(parsed)
})
/**
 * 同步文本并将有效颜色回传给父级草稿。
 * @returns 无
 */
function commit(): void { syncFields(); emit('update:modelValue', rgba.value) }

/**
 * 将指针位置转换为色板饱和度和明度。
 * @param event 色板上的指针事件
 * @returns 无
 */
function pickPoint(event: PointerEvent): void {
  if (props.disabled || !(event.currentTarget instanceof HTMLElement)) return
  const bounds = event.currentTarget.getBoundingClientRect()
  if (!bounds.width || !bounds.height) return
  hsv.s = clamp((event.clientX - bounds.left) / bounds.width)
  hsv.v = 1 - clamp((event.clientY - bounds.top) / bounds.height)
  commit()
}
/**
 * 捕获指针并开始拖动选色。
 * @param event 指针按下事件
 * @returns 无
 */
function start(event: PointerEvent): void {
  if (props.disabled || event.button !== 0 || !(event.currentTarget instanceof HTMLElement)) return
  event.preventDefault(); pointer = event.pointerId
  event.currentTarget.focus(); event.currentTarget.setPointerCapture(event.pointerId)
  pickPoint(event)
}
/**
 * 在当前指针拖动时更新颜色。
 * @param event 指针移动事件
 * @returns 无
 */
function move(event: PointerEvent): void { if (pointer === event.pointerId) pickPoint(event) }
/**
 * 结束拖动并释放指针捕获。
 * @param event 指针结束事件
 * @returns 无
 */
function stop(event: PointerEvent): void {
  if (pointer !== event.pointerId) return
  pointer = null
  if (event.currentTarget instanceof HTMLElement && event.currentTarget.hasPointerCapture(event.pointerId)) event.currentTarget.releasePointerCapture(event.pointerId)
}
/**
 * 用方向键调整色板坐标，Shift 使用较大步长。
 * @param event 键盘事件
 * @returns 无
 */
function keyPoint(event: KeyboardEvent): void {
  if (props.disabled || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return
  event.preventDefault()
  const step = event.shiftKey ? .1 : .01
  if (event.key === 'ArrowLeft') hsv.s = clamp(hsv.s - step)
  if (event.key === 'ArrowRight') hsv.s = clamp(hsv.s + step)
  if (event.key === 'ArrowUp') hsv.v = clamp(hsv.v + step)
  if (event.key === 'ArrowDown') hsv.v = clamp(hsv.v - step)
  commit()
}
/**
 * 将滑动条数值写入色相或透明度。
 * @param event 范围输入事件
 * @param channel hue 或 alpha
 * @returns 无
 */
function range(event: Event, channel: 'hue' | 'alpha'): void {
  if (props.disabled || !(event.target instanceof HTMLInputElement)) return
  const value = Number(event.target.value)
  if (!Number.isFinite(value)) return
  if (channel === 'hue') hsv.h = clamp(value, 0, 360)
  else alpha.value = clamp(value)
  commit()
}
/**
 * 检查颜色文本并更新表单校验提示。
 * @param event 颜色文本输入事件
 * @returns 无
 */
function validate(event: Event): void {
  if (!(event.target instanceof HTMLInputElement)) return
  const valid = parseColor(event.target.value) !== null
  error.value = valid ? '' : '请输入有效的 RGB、RGBA 或 HEX 颜色。'
  event.target.setCustomValidity(error.value)
}
/**
 * 将有效的 RGBA 或 HEX 文本提交到草稿。
 * @param event 文本变更或回车事件
 * @returns 无
 */
function applyText(event: Event): void {
  if (props.disabled || !(event.target instanceof HTMLInputElement)) return
  validate(event)
  const parsed = parseColor(event.target.value)
  if (parsed) { loadColor(parsed); emit('update:modelValue', rgba.value) }
}
</script>

<template>
  <section :id="id" class="color-picker" role="group" :aria-label="`${label} RGBA 调色盘`">
    <div class="color-picker-heading"><strong>RGBA 调色盘</strong><span>拖动选色</span></div>
    <div class="color-palette" role="slider" :tabindex="disabled ? -1 : 0" :aria-label="`${label}饱和度与明度`" :aria-disabled="disabled" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="Math.round(hsv.s * 100)" :aria-valuetext="`饱和度 ${Math.round(hsv.s * 100)}%，明度 ${Math.round(hsv.v * 100)}%`" :style="{ backgroundColor: `hsl(${hsv.h},100%,50%)` }" @pointerdown="start" @pointermove="move" @pointerup="stop" @pointercancel="stop" @lostpointercapture="pointer = null" @keydown="keyPoint">
      <span class="color-point" :style="{ left: `${hsv.s * 100}%`, top: `${(1 - hsv.v) * 100}%`, backgroundColor: opaque }"></span>
    </div>
    <div class="color-range-label"><label :for="`${id}-hue`">色相</label><span>{{ Math.round(hsv.h) }}°</span></div>
    <input :id="`${id}-hue`" class="color-range hue-range" type="range" min="0" max="360" step="1" :value="hsv.h" :disabled="disabled" :aria-label="`${label}色相`" @input="range($event, 'hue')" />
    <div class="color-range-label"><label :for="`${id}-alpha`">透明度</label><span>{{ percentage }}%</span></div>
    <div class="alpha-rail checkerboard"><input :id="`${id}-alpha`" class="color-range" type="range" min="0" max="1" step="0.01" :value="alpha" :disabled="disabled" :aria-label="`${label}透明度`" :style="{ backgroundImage: `linear-gradient(to right, transparent, ${opaque})` }" @input="range($event, 'alpha')" /></div>
    <div class="color-result">
      <div class="color-final checkerboard" aria-label="最终颜色预览"><span :style="{ backgroundColor: rgbaText(color) }"></span></div>
      <div class="color-values">
        <label :for="`${id}-rgba`">RGBA</label><input :id="`${id}-rgba`" ref="rgbaField" v-model="rgba" :disabled="disabled" maxlength="64" spellcheck="false" @input="validate" @change="applyText" @keydown.enter.prevent="applyText" />
        <label :for="`${id}-hex`">HEX</label><input :id="`${id}-hex`" ref="hexField" v-model="hex" :disabled="disabled" maxlength="9" spellcheck="false" @input="validate" @change="applyText" @keydown.enter.prevent="applyText" />
      </div>
    </div>
    <p v-if="error" role="alert" class="color-error">{{ error }}</p>
    <p class="color-help">透明色的 HEX 末两位为透明度；修改文本后按回车或移开焦点生效。</p>
  </section>
</template>

<style scoped>
.color-picker { width: 100%; max-width: 360px; min-width: 0; margin-top: 12px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; color: #292333; box-shadow: 0 6px 20px #0000000d; }
.color-picker-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 13px; }
.color-picker-heading span { color: #808080; font-size: 11px; }
.color-palette { position: relative; width: 100%; max-width: 240px; aspect-ratio: 1; margin: 0 auto; overflow: hidden; border-radius: 10px; background-image: linear-gradient(to top, #000, transparent), linear-gradient(to right, #fff, transparent); cursor: crosshair; touch-action: none; }
.color-palette:focus { outline: none; }
.color-point { position: absolute; width: 14px; height: 14px; border: 2px solid #fff; border-radius: 50%; box-shadow: 0 0 0 1px #333; transform: translate(-50%, -50%); pointer-events: none; }
.color-range-label { display: flex; justify-content: space-between; margin: 14px 0 7px; font-size: 12px; }
.color-range-label span { color: #777; font-variant-numeric: tabular-nums; }
.color-range { display: block; appearance: none; width: 100%; height: 12px; margin: 0; padding: 0; border: 0; border-radius: 8px; cursor: pointer; background-color: transparent; }
.hue-range { background-image: linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00); }
.color-range::-webkit-slider-thumb { appearance: none; width: 18px; height: 18px; border: 2px solid #fff; border-radius: 50%; background: #fff; box-shadow: 0 0 0 1px #999, 0 1px 4px #0003; }
.color-range::-moz-range-thumb { width: 14px; height: 14px; border: 2px solid #fff; border-radius: 50%; background: #fff; box-shadow: 0 0 0 1px #999; }
.checkerboard { background-color: #fff; background-image: conic-gradient(#e5e7eb 25%, #fff 0 50%, #e5e7eb 0 75%, #fff 0); background-size: 10px 10px; }
.alpha-rail { border-radius: 8px; }
.color-result { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: stretch; gap: 12px; margin-top: 18px; }
.color-final { position: relative; min-height: 86px; overflow: hidden; border: 1px solid #e5e7eb; border-radius: 9px; }
.color-final span { position: absolute; inset: 0; }
.color-values { min-width: 0; }
.color-values label { display: block; margin-bottom: 3px; font-size: 10px; font-weight: 600; color: #6b7280; }
.color-values input { width: 100%; min-width: 0; margin-bottom: 6px; padding: 5px 6px; border: 1px solid #e5e7eb; border-radius: 5px; background: #f9fafb; color: #292333; font-family: ui-monospace, monospace; font-size: 11px; }
.color-values input:last-child { margin-bottom: 0; }
.color-help { margin-top: 10px; color: #808080; font-size: 10px; line-height: 1.6; }
.color-error { margin-top: 8px; color: #dc2626; font-size: 12px; }
</style>
