import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'
import { readFileSync } from 'node:fs'

export default defineConfig({
  plugins: [
    {
      name: 'browser-guard',
      // 开发页和 PHP 页面使用同一份检测脚本、基础样式与提示内容。
      transformIndexHtml: { order: 'pre', handler: (html) => html
        .replace('<!-- browser-guard-head -->', readFileSync(new URL('../src/view/template_browser_guard_head.html', import.meta.url), 'utf8'))
        .replace('<!-- browser-guard-body -->', readFileSync(new URL('../src/view/template_browser_guard_body.html', import.meta.url), 'utf8')) },
    },
    vue({ template: { transformAssetUrls: { includeAbsolute: false } } }), tailwindcss(),
  ],
  base: '/build/',
  publicDir: false,
  server: {
    proxy: Object.fromEntries(['/api.php', '/env', '/static', '/install', '/favicon.ico'].map((path) => [path, 'http://127.0.0.1:8080'])),
  },
  build: {
    outDir: fileURLToPath(new URL('../public/build', import.meta.url)),
    emptyOutDir: true,
    manifest: true,
    cssCodeSplit: true,
    target: ['chrome111', 'firefox128', 'safari16.4'],
  },
})
