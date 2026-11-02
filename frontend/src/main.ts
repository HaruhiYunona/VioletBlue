/** 挂载 Vue，等待首屏任务与样式完成后移除加载界面。 */
import { createApp } from 'vue'
import App from './App.vue'
import { router } from './router/router_frontend'
import './styles/style_main.css'
import { nextTick } from 'vue'
import { waitForStartup } from './state/state_loading'

createApp(App).use(router).mount('#app')

void router.isReady().then(async () => {
  // 路由组件挂载后收集首屏歌曲任务，再等待样式和字体。
  await nextTick()
  await waitForStartup()
  await Promise.race([
    Promise.all(Array.from(document.querySelectorAll<HTMLLinkElement>('link.boot-style')).map((link) => link.sheet ? Promise.resolve() : new Promise<void>((resolve, reject) => {
      link.addEventListener('load', () => resolve(), { once: true }); link.addEventListener('error', () => reject(new Error('样式加载失败')), { once: true })
    }))),
    new Promise<never>((_, reject) => setTimeout(() => reject(new Error('资源加载超时')), 10000)),
  ])
  await Promise.race([document.fonts.ready, new Promise<void>((resolve) => setTimeout(resolve, 1500))])
  requestAnimationFrame(() => {
    // 同一帧显示主界面并触发背景懒加载，加载层随后淡出移除。
    document.body.removeAttribute('data-booting')
    const splash = document.getElementById('boot-loader')
    splash?.setAttribute('aria-hidden', 'true')
    splash?.classList.add('is-complete')
    setTimeout(() => splash?.remove(), 220)
    window.dispatchEvent(new Event('vb:app-ready'))
    if (window.parent !== window) window.parent.postMessage({ type: 'vb-theme-preview-ready' }, location.origin)
  })
}).catch(() => {
  const splash = document.getElementById('boot-loader')
  splash?.classList.add('boot-error')
  const label = splash?.querySelector('p')
  if (label) label.textContent = '加载失败，请刷新重试'
})
