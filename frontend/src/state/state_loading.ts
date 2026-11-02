/** 首屏任务与操作遮罩状态；并发操作按独立编号结束。 */
import { reactive } from 'vue'

export const loadingState = reactive({ visible: false, label: '' })
const pending = new Map<number, string>()
let sequence = 0
let delay: ReturnType<typeof setTimeout> | undefined
const startupTasks: Promise<unknown>[] = []
/**
 * 收集首次展示页面前需要等待的任务。
 * @param task 首屏异步任务
 * @returns 无
 */
export function trackStartup(task: Promise<unknown>): void { if (document.body.hasAttribute('data-booting')) startupTasks.push(task) }
/**
 * 等待已登记的首屏任务结束。
 * @returns 所有任务结束后完成的 Promise
 */
export async function waitForStartup(): Promise<void> { await Promise.allSettled(startupTasks.splice(0)) }

/** 用途：开始可并发的前台加载提示，短请求不闪屏；入参：提示文字；返回值：结束函数。 */
export function beginLoading(label: string): (() => void) & { update: (label: string) => void } {
  const id = ++sequence
  pending.set(id, label); loadingState.label = label
  if (!delay && !loadingState.visible) delay = setTimeout(() => { delay = undefined; loadingState.visible = pending.size > 0 }, 140)
  let finished = false
  const finish = (): void => {
    if (finished) return
    finished = true; pending.delete(id)
    if (!pending.size) {
      clearTimeout(delay); delay = undefined; loadingState.visible = false
    } else loadingState.label = [...pending.values()].at(-1) ?? '正在处理…'
  }
  return Object.assign(finish, { update: (value: string): void => { if (!finished) { pending.set(id, value); loadingState.label = value } } })
}
