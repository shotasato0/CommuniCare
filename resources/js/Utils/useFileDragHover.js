import { ref, onMounted, onUnmounted } from 'vue'

// 共通ドラッグホバー検知フック
// - dropCallback: (FileList|Array<File>) => void を受け取り、ドロップ時に呼ばれる
export function useFileDragHover(dropCallback) {
  const isDragOver = ref(false)
  let dragDepth = 0
  let _preventIfFiles
  let hideTimer = null // タイマーID

  // 即座に表示する
  const setVisible = () => {
    if (hideTimer) { //もし非表示タイマーがあればキャンセル
      clearTimeout(hideTimer) // タイマークリア
      hideTimer = null // タイマーIDクリア
    }
    isDragOver.value = true // ドラッグオーバー状態に
  }

  // 指定時間後に非表示にする
  const scheduleHide = (delay = 100) => {
    if (hideTimer) clearTimeout(hideTimer)
    hideTimer = setTimeout(() => {
      isDragOver.value = false
      hideTimer = null
    }, delay)
  }

  const hasFiles = (e) => {
    const types = e?.dataTransfer?.types
    return types && (types.includes && types.includes('Files'))
  }

  const onTextDragEnter = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth++
    setVisible() // 即座に表示
  }

  const onTextDragOver = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    setVisible() // 即座に表示
  }

  const onTextDragLeave = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth = Math.max(0, dragDepth - 1)
    if (dragDepth === 0) {
      scheduleHide(120)
    }
  }

  const onTextDrop = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth = 0
    if (hideTimer) { //もし非表示タイマーがあればキャンセル
      clearTimeout(hideTimer)
      hideTimer = null
    }
    isDragOver.value = false
    const files = e.dataTransfer?.files || []
    if (files && files.length > 0 && typeof dropCallback === 'function') {
      dropCallback(files)
    }
  }

  const onTextMouseLeave = () => {
    dragDepth = 0
    isDragOver.value = false
  }

  onMounted(() => {
    _preventIfFiles = (e) => {
      if (hasFiles(e)) e.preventDefault()
    }
    window.addEventListener('dragover', _preventIfFiles)
    window.addEventListener('drop', _preventIfFiles)
  })

  onUnmounted(() => {
    if (_preventIfFiles) {
      window.removeEventListener('dragover', _preventIfFiles)
      window.removeEventListener('drop', _preventIfFiles)
    }
  })

  return {
    isDragOver,
    onTextDragEnter,
    onTextDragOver,
    onTextDragLeave,
    onTextDrop,
    onTextMouseLeave,
  }
}
