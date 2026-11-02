<script setup lang="ts">
/** 前后台共用的仓库及许可证链接。 */
defineProps<{ inline?: boolean }>()
</script>
<template>
  <a class="repository-info" :class="{ 'is-inline': inline }" href="https://github.com/HaruhiYunona/VioletBlue/" target="_blank" rel="noopener noreferrer">
    <span>Github@HaruhiYunona</span>
    <span v-if="inline" class="footer-divider" aria-hidden="true">|</span>
    <span>Obj#VioletBlue - MIT License</span>
  </a>
</template>
