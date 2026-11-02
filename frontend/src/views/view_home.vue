<script setup lang="ts">
/** 后台功能导航卡片。 */
import { RouterLink } from 'vue-router'
const cards = [
  ['新增歌曲', '单独新增一首歌曲。UI 更加直观简洁。', '/admin/songs/new', 'music'],
  ['批量新增', '从 Excel 表格复制内容，批量增加歌曲。', '/admin/batch', 'layers'],
  ['修改歌曲', '打开歌单，点击歌曲修改歌曲信息。', '/', 'edit-3'],
  ['网站设置', '修改公告、歌曲标签与管理员密码。', '/admin/options', 'toggle-left'],
  ['日志与更新', '获取版本信息或查看更新日志。', '/admin/updates', 'download-cloud'],
  ['基础设置', '修改网站信息、B站 MID 与静态资源。', '/admin/settings', 'settings'],
  ['网站配色', '修改前台颜色与电脑端右栏卡片，实时预览并保存。', '/admin/theme', 'sidebar'],
  ['联系方式', '查看原作者的联系方式。', '/admin/info/contact', 'smartphone'],
  ['利用协议', '查看原项目许可证与用户公约。', '/admin/info/agreement', 'git-pull-request'],
]
</script>
<template>
  <h1 class="section-title mb-6">管理功能列表</h1>
  <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
    <RouterLink v-for="card in cards" :key="card[2]" :to="card[2]" class="min-h-[120px] rounded-md bg-[var(--song-card)] p-5 shadow-sm transition-shadow hover:shadow-md">
      <h2 class="song-name flex items-center gap-2 text-lg font-bold"><img :src="`/static/img/${card[3]}.svg`" alt="" class="admin-card-icon size-[18px] shrink-0" />{{ card[0] }}</h2>
      <p class="mt-2 text-sm text-[var(--all-text)]">{{ card[1] }}</p>
    </RouterLink>
    <a href="https://github.com/HaruhiYunona/VioletBlue/" target="_blank" rel="noopener noreferrer" class="min-h-[120px] rounded-md bg-[var(--song-card)] p-5 shadow-sm"><h2 class="song-name flex items-center gap-2 text-lg font-bold"><img src="/static/img/github.svg" alt="" class="admin-card-icon size-[18px] shrink-0" />代码仓库</h2><p class="mt-2 text-sm text-[var(--all-text)]">访问作者项目仓库，获取最新信息。</p></a>
  </div>
</template>

<style scoped>
.admin-card-icon { filter: brightness(0); }
</style>
