/** 浏览器检测通过后再加载 Vue、路由与主应用样式。 */
declare global { interface Window { __VB_BROWSER_SUPPORTED__?: boolean } }

if (window.__VB_BROWSER_SUPPORTED__ === true) {
  void import('./main').catch(() => window.dispatchEvent(new Event('vb:boot-error')))
}

export {}
