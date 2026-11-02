/** 背景图片的延迟请求、解码和淡入。 */
let promise: Promise<void> | undefined

/**
 * 在首屏绘制后空闲时请求背景，解码成功后淡入。
 * @param allowed 开始请求前再次检查背景是否启用的函数
 * @returns 背景加载或跳过后完成的任务
 */
export function loadBackground(allowed: () => boolean): Promise<void> {
  return promise ??= new Promise<void>((resolve) => {
    const load = (): void => {
      if (!allowed()) { promise = undefined; resolve(); return }
      const image = new Image()
      image.fetchPriority = 'low'
      image.onload = async () => {
        try { await image.decode() } catch { /* 图片已加载时仍可展示。 */ }
        document.documentElement.style.setProperty('--loaded-background-image', 'url("/static/img/background.png")')
        document.documentElement.classList.add('background-ready'); resolve()
      }
      image.onerror = () => resolve()
      image.src = '/static/img/background.png'
    }
    // 先让主界面绘制，图片不会参与首屏资源阻塞。
    requestAnimationFrame(() => requestAnimationFrame(() => {
      if ('requestIdleCallback' in window) window.requestIdleCallback(load, { timeout: 1200 })
      else setTimeout(load, 120)
    }))
  })
}
