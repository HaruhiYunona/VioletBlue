<script setup lang="ts">
/** 歌曲卡片，支持手机端完整卡片和紧凑单行。 */
import { siteState } from '../state/state_site'
import type { MobileSongStyle, Song } from '../types/type_api'
defineProps<{ song: Song; mobileStyle?: MobileSongStyle; mobilePreview?: boolean }>()
defineEmits<{ select: [song: Song] }>()
</script>
<template>
  <button type="button" class="song-card transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-[var(--focus-ring)]" :class="{ 'has-claim': song.notice, 'is-mobile-compact': (mobileStyle ?? siteState.theme.mobile_song_style) === 'compact', 'is-mobile-preview': mobilePreview }" :data-song-id="song.uid" @click="$emit('select', song)">
    <span class="flex min-w-0 items-start justify-between gap-2">
      <span class="song-name min-w-0 truncate text-[15px] lg:text-base">{{ song.song }} - {{ song.singer }}</span>
      <span v-if="song.notice" class="max-w-[35%] shrink-0 truncate text-xs" :title="song.notice">{{ song.notice }}</span>
    </span>
    <span class="song-tags mt-2 flex flex-wrap gap-1.5"><span v-for="tag in song.type" :key="tag" class="song-tag">{{ tag }}</span></span>
  </button>
</template>
