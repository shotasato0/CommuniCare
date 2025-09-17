import { ref, onMounted, onUnmounted } from 'vue'

// 共通ドラッグホバー検知フック
// - dropCallback: (FileList|Array<File>) => void を受け取り、ドロップ時に呼ばれる
export function useFileDragHover(dropCallback) {
  const isDragOver = ref(false)
  let dragDepth = 0
  let _preventIfFiles

  const hasFiles = (e) => {
    const types = e?.dataTransfer?.types
    return types && (types.includes && types.includes('Files'))
  }

  const onTextDragEnter = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth++
    isDragOver.value = true
  }

  const onTextDragOver = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    isDragOver.value = true
  }

  const onTextDragLeave = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth = Math.max(0, dragDepth - 1)
    if (dragDepth === 0) {
      isDragOver.value = false
    }
  }

  const onTextDrop = (e) => {
    if (!hasFiles(e)) return
    e.preventDefault()
    dragDepth = 0
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

