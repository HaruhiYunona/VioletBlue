import { isRecord } from './type_api'

export interface MigrationDatabase {
  database: string; prefix: string; song_table: string; others_table: string; total: number; current: boolean
}
export interface MigrationSong {
  uid: number; name: string; singer: string; tags: string[]; note: string; time: number
}
export interface MigrationGroup {
  name: string; singer: string; rows: MigrationSong[]; merged: MigrationSong
}
export interface MigrationPreview extends MigrationDatabase {
  fingerprint: string; duplicate_count: number; remaining: number; groups: MigrationGroup[]
}
export interface MigrationResult {
  database: string; prefix: string; total: number; removed: number; backups: { song: string; others: string }; login_required: boolean
}

/** 校验数据库列表项；入参：接口数据；返回：是否为迁移目标。 */
function isDatabase(value: unknown): value is MigrationDatabase {
  return isRecord(value) && ['database', 'prefix', 'song_table', 'others_table'].every((key) => typeof value[key] === 'string')
    && typeof value.total === 'number' && typeof value.current === 'boolean'
}
/** 校验重复歌曲字段；入参：接口数据；返回：是否为歌曲。 */
function isSong(value: unknown): value is MigrationSong {
  return isRecord(value) && typeof value.uid === 'number' && typeof value.time === 'number'
    && ['name', 'singer', 'note'].every((key) => typeof value[key] === 'string')
    && Array.isArray(value.tags) && value.tags.every((tag) => typeof tag === 'string')
}
export function isMigrationDatabases(value: unknown): value is MigrationDatabase[] {
  return Array.isArray(value) && value.every(isDatabase)
}
export function isMigrationPreview(value: unknown): value is MigrationPreview {
  return isDatabase(value) && isRecord(value) && typeof value.fingerprint === 'string'
    && typeof value.duplicate_count === 'number' && typeof value.remaining === 'number'
    && Array.isArray(value.groups) && value.groups.every((group) => isRecord(group)
      && typeof group.name === 'string' && typeof group.singer === 'string'
      && Array.isArray(group.rows) && group.rows.every(isSong) && isSong(group.merged))
}
export function isMigrationResult(value: unknown): value is MigrationResult {
  return isRecord(value) && typeof value.database === 'string' && typeof value.prefix === 'string'
    && typeof value.total === 'number' && typeof value.removed === 'number' && typeof value.login_required === 'boolean'
    && isRecord(value.backups) && typeof value.backups.song === 'string' && typeof value.backups.others === 'string'
}
