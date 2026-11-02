/** 保存独立草稿快照，提供有容量限制的撤销与重做。 */
export class DraftHistory<T> {
  private entries: T[]
  private index = 0
  /**
   * 复制初始状态并设置历史容量。
   * @param initial 初始草稿
   * @param clone 创建独立快照的函数
   * @param limit 最大快照数量
   */
  constructor(initial: T, private readonly clone: (value: T) => T, private readonly limit = 100) { this.entries = [clone(initial)] }
  /**
   * 检查当前快照之前是否还有记录。
   * @returns 是否可撤销
   */
  get canUndo(): boolean { return this.index > 0 }
  /**
   * 检查当前快照之后是否还有记录。
   * @returns 是否可重做
   */
  get canRedo(): boolean { return this.index < this.entries.length - 1 }
  /**
   * 记录发生变化的草稿并丢弃旧重做分支。
   * @param value 当前草稿
   * @returns 无
   */
  checkpoint(value: T): void {
    if (JSON.stringify(value) === JSON.stringify(this.entries[this.index])) return
    this.entries.splice(this.index + 1); this.entries.push(this.clone(value))
    if (this.entries.length > this.limit) this.entries.shift()
    this.index = this.entries.length - 1
  }
  /**
   * 移动到上一份快照。
   * @returns 快照副本，无可用记录时为 null
   */
  undo(): T | null { return this.canUndo ? this.clone(this.entries[--this.index]) : null }
  /**
   * 移动到下一份快照。
   * @returns 快照副本，无可用记录时为 null
   */
  redo(): T | null { return this.canRedo ? this.clone(this.entries[++this.index]) : null }
}
