/** 前端哈希路由及按需加载，进入后台前检查会话。 */
import { createRouter, createWebHashHistory } from 'vue-router'
import { initialize, notify, siteState } from '../state/state_site'
import { ErrorMapper } from '../utils/util_error'

export const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: () => import('../views/view_playlist.vue') },
    { path: '/admin/login', component: () => import('../views/view_login.vue') },
    {
      path: '/admin', component: () => import('../views/view_admin.vue'), meta: { requiresAuth: true },
      children: [
        { path: '', component: () => import('../views/view_home.vue') },
        { path: 'songs/new', component: () => import('../views/view_song.vue') },
        { path: 'songs/:uid', component: () => import('../views/view_song.vue') },
        { path: 'batch', component: () => import('../views/view_batch.vue') },
        { path: 'settings', component: () => import('../views/view_settings.vue') },
        { path: 'options', component: () => import('../views/view_options.vue') },
        { path: 'theme', component: () => import('../views/view_theme.vue') },
        { path: 'upload/:type', component: () => import('../views/view_upload.vue') },
        { path: 'updates', component: () => import('../views/view_updates.vue') },
        { path: 'migration', component: () => import('../views/view_migration.vue') },
        { path: 'info/:page', component: () => import('../views/view_information.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  // 初始化任务复用同一个 Promise，后台路由等待会话校验后进入。
  try { await initialize() } catch { return true }
  if (to.matched.some((record) => record.meta.requiresAuth) && !siteState.authenticated) return { path: '/admin/login', query: { next: to.fullPath } }
  if (to.path === '/admin/login' && siteState.authenticated) return '/admin'
  return true
})
window.addEventListener('vb:unauthorized', () => {
  siteState.authenticated = false
  const current = router.currentRoute.value
  if (current.path.startsWith('/admin') && current.path !== '/admin/login') {
    notify(ErrorMapper.fromResponse(401, undefined).message, 'error')
    void router.replace({ path: '/admin/login', query: { next: current.fullPath } })
  }
})
