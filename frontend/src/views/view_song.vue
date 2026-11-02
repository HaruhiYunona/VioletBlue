<script setup lang="ts">
/** 歌曲新增、编辑与删除页面。 */
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { isBoolean, isSongInput, isStrings } from '../types/type_api'
import { messageOf, request } from '../api/api_client'
import { notify } from '../state/state_site'
const route = useRoute(); const router = useRouter()
const uid = computed(() => typeof route.params.uid === 'string' ? Number(route.params.uid) : 0)
const editing = computed(() => uid.value > 0)
const song = reactive({ name: '', singer: '', claim: '', tags: [] as string[] })
const tags = ref<string[]>([]); const busy = ref(false); const error = ref(''); const confirming = ref(false)
let revision = 0
watch(uid, async () => {
  const current = ++revision
  busy.value = true
  error.value = ''
  try {
    const [values, detail] = await Promise.all([
      request('getTagList', isStrings),
      editing.value ? request('detail', isSongInput, { uid: uid.value }) : Promise.resolve({ name: '', singer: '', claim: '', tags: [] as string[] }),
    ])
    if (current !== revision) return
    Object.assign(song, detail)
    tags.value = [...new Set([...values.filter((tag) => tag !== '全部'), ...detail.tags])]
  } catch (failure: unknown) { if (current === revision) error.value = messageOf(failure) }
  finally { if (current === revision) busy.value = false }
}, { immediate: true })
onBeforeUnmount(() => { revision++ })
/**
 * 切换歌曲的分类标签。
 * @param tag 分类名
 * @returns 无
 */
function toggle(tag: string): void { song.tags = song.tags.includes(tag) ? song.tags.filter((value) => value !== tag) : [...song.tags, tag] }
/**
 * 按当前路由新增或修改歌曲。
 * @returns 保存完成的任务
 */
async function save(): Promise<void> {
  if ([...song.name.trim()].length > 300 || [...song.singer.trim()].length > 300) { error.value = '歌名和歌手最多 300 字符'; return }
  busy.value = true; error.value = ''
  try { await request(editing.value ? 'edit' : 'submitSong', isBoolean, { ...song, ...(editing.value ? { uid: uid.value } : {}) }); notify(editing.value ? '歌曲修改成功' : '歌曲添加成功'); if (!editing.value) { song.name = ''; song.singer = ''; song.claim = '' } }
  catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
/**
 * 删除当前歌曲并返回歌单。
 * @returns 删除完成的任务
 */
async function remove(): Promise<void> {
  busy.value = true
  try { await request('delete', isBoolean, { uid: uid.value }); notify('歌曲已删除'); await router.push('/') }
  catch (failure: unknown) { error.value = messageOf(failure) } finally { busy.value = false }
}
</script>
<template>
  <h1 class="section-title mb-6">{{ editing ? '修改歌曲' : '新增歌曲' }}</h1>
  <form @submit.prevent="save">
    <fieldset :disabled="busy" class="min-w-0">
    <label class="form-label" for="song-name">歌名</label><input id="song-name" v-model="song.name" required maxlength="600" class="form-input" /><p class="form-help">最多 300 字符。</p>
    <label class="form-label" for="song-singer">歌手</label><input id="song-singer" v-model="song.singer" required maxlength="600" class="form-input" /><p class="form-help">最多 300 字符。</p>
    <label class="form-label" for="song-claim">冠名</label><input id="song-claim" v-model="song.claim" maxlength="5000" class="form-input" />
    <fieldset class="mt-6"><legend class="font-semibold">歌曲标签</legend><div class="mt-3 flex flex-wrap gap-2"><button v-for="tag in tags" :key="tag" type="button" class="tag-button" :aria-pressed="song.tags.includes(tag)" @click="toggle(tag)">{{ tag }}</button></div><p v-if="!tags.length" class="form-help">请先在网站设置中添加标签。<RouterLink to="/admin/options" class="underline">打开设置</RouterLink></p></fieldset>
    <p v-if="error" role="alert" class="mt-5 text-sm text-[var(--error-text)]">{{ error }}</p>
    <div class="mt-8 flex flex-wrap gap-3"><button class="action-button" :disabled="busy || !song.tags.length">{{ busy ? '处理中…' : '保存歌曲' }}</button><button v-if="editing" type="button" class="quiet-button text-[var(--error-text)]" @click="confirming = true">删除歌曲</button></div>
    </fieldset>
  </form>
  <div v-if="confirming" class="fixed inset-0 z-40 flex items-center justify-center bg-[var(--dialog-overlay)] p-5" role="alertdialog" aria-modal="true" aria-labelledby="delete-title">
    <div class="w-full max-w-md rounded-xl bg-[var(--dialog-background)] p-6"><h2 id="delete-title" class="text-lg font-bold">删除歌曲</h2><p class="my-5 break-words">确认删除《{{ song.name }}》吗？</p><div class="flex justify-end gap-3"><button class="quiet-button" @click="confirming = false">取消</button><button class="action-button" :disabled="busy" @click="remove">确认删除</button></div></div>
  </div>
</template>
