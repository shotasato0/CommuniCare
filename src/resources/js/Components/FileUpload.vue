<template>
  <div class="file-upload-container">

    <!-- 隠しファイル入力 -->
    <input
      ref="fileInput"
      type="file"
      multiple
      :accept="acceptedTypes"
      @change="handleFileSelect"
      class="hidden-file-input"
    >

    <!-- エラー表示 -->
    <div v-if="hasError" class="error-message">
      <i class="bi bi-exclamation-triangle-fill"></i>
      {{ errorMessage }}
    </div>

    <!-- アップロード済みファイル一覧 -->
    <div v-if="files.length > 0" v-show="visible" class="uploaded-files">
      <h4 class="uploaded-files-title">
        <i class="bi bi-files"></i>
        添付ファイル ({{ files.length }})
      </h4>
      
      <div class="file-list">
        <div
          v-for="(file, index) in files"
          :key="`file-${index}`"
          class="file-item"
        >
          <!-- ファイルアイコン -->
          <div class="file-icon">
            <i :class="getFileIcon(file)"></i>
          </div>
          
          <!-- ファイル情報 -->
          <div class="file-info">
            <span class="file-name">{{ file.name }}</span>
            <span class="file-size">{{ formatFileSize(file.size) }}</span>
          </div>
          
          <!-- プレビュー（画像の場合） -->
          <div v-if="isImage(file)" class="file-preview">
            <img :src="getPreviewUrl(file)" :alt="file.name" />
          </div>
          
          <!-- 削除ボタン -->
          <button
            type="button"
            @click="removeFile(index)"
            class="remove-file-button"
            :title="`${file.name}を削除`"
          >
            <i class="bi bi-x-circle-fill"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FileUpload',
  emits: ['files-changed', 'error'],
  
  props: {
    // パネルの表示/非表示（機能は常時有効）
    visible: { type: Boolean, default: true },
    // 最大ファイル数（制約統一: 10）
    maxFiles: { type: Number, default: 10 },
  },

  data() {
    return {
      files: [],
      hasError: false,
      errorMessage: '',
      acceptedTypes: '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv',
      maxFileSize: 10 * 1024 * 1024, // 10MB
      supportedMimeTypes: [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv'
      ],
      // 生成したオブジェクトURLをファイル単位でキャッシュ
      // リーク防止のため、削除時/破棄時にrevokeする
      previewUrlKey: '_previewUrl'
    }
  },

  methods: {
    // 親コンポーネントから直接ファイルを投入するための公開メソッド
    processExternalFiles(newFiles) {
      if (!newFiles || newFiles.length === 0) return
      const list = Array.from(newFiles)
      this.processFiles(list)
    },

    // 親からファイル選択ダイアログを開くための公開メソッド
    openFileDialog() {
      if (this.$refs.fileInput) this.$refs.fileInput.click()
    },
    handleFileSelect(e) {
      const selectedFiles = Array.from(e.target.files)
      this.processFiles(selectedFiles)
    },

    processFiles(newFiles) {
      this.clearError()
      
      const validFiles = []

      // 件数上限チェック（既存+追加がmaxFilesを超えないように）
      const remaining = this.maxFiles - this.files.length
      if (newFiles.length > remaining) {
        this.showError(`一度に添付できるファイルは最大${this.maxFiles}個までです`)
      }
      const slice = newFiles.slice(0, Math.max(0, remaining))
      
      for (const file of slice) {
        if (this.validateFile(file)) {
          validFiles.push(file)
        }
      }
      
      if (validFiles.length > 0) {
        this.files = [...this.files, ...validFiles]
        this.$emit('files-changed', this.files)
      }
    },

    validateFile(file) {
      // ファイルサイズチェック
      if (file.size > this.maxFileSize) {
        this.showError(`${file.name} はファイルサイズが大きすぎます（最大10MB）`)
        return false
      }
      
      // MIMEタイプチェック
      if (!this.supportedMimeTypes.includes(file.type)) {
        this.showError(`${file.name} はサポートされていないファイル形式です`)
        return false
      }
      
      // 重複チェック
      if (this.files.some(existingFile => 
        existingFile.name === file.name && 
        existingFile.size === file.size
      )) {
        this.showError(`${file.name} は既に追加されています`)
        return false
      }
      
      return true
    },

    removeFile(index) {
      const file = this.files[index]
      // 画像のプレビューURLを確実にクリーンアップ
      if (file && this.isImage(file) && file[this.previewUrlKey]) {
        URL.revokeObjectURL(file[this.previewUrlKey])
        delete file[this.previewUrlKey]
      }
      this.files.splice(index, 1)
      this.$emit('files-changed', this.files)
    },

    getFileIcon(file) {
      const type = file.type.toLowerCase()
      
      if (type.startsWith('image/')) return 'bi bi-file-earmark-image-fill text-blue-600'
      if (type === 'application/pdf') return 'bi bi-file-earmark-pdf-fill text-red-600'
      if (type.includes('word') || type.includes('document')) return 'bi bi-file-earmark-word-fill text-blue-800'
      if (type.includes('sheet') || type.includes('excel')) return 'bi bi-file-earmark-excel-fill text-green-600'
      if (type.startsWith('text/')) return 'bi bi-file-earmark-text-fill text-gray-600'
      
      return 'bi bi-file-earmark-fill text-gray-500'
    },

    isImage(file) {
      return file.type.startsWith('image/')
    },

    getPreviewUrl(file) {
      if (!this.isImage(file)) return null
      if (!file[this.previewUrlKey]) {
        file[this.previewUrlKey] = URL.createObjectURL(file)
      }
      return file[this.previewUrlKey]
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },

    showError(message) {
      this.hasError = true
      this.errorMessage = message
      this.$emit('error', message)
      
      // 3秒後にエラーを自動的に消す
      setTimeout(() => {
        this.clearError()
      }, 3000)
    },

    clearError() {
      this.hasError = false
      this.errorMessage = ''
    },

    // 外部からのリセット用
    reset() {
      this.files = []
      this.clearError()
      this.$refs.fileInput.value = ''
    },
    
    // 外部からファイル取得用
    getFiles() {
      return this.files
    }
  },

  beforeUnmount() {
    // プレビュー用のオブジェクトURLをクリーンアップ
    this.files.forEach(file => {
      if (this.isImage(file) && file[this.previewUrlKey]) {
        URL.revokeObjectURL(file[this.previewUrlKey])
        delete file[this.previewUrlKey]
      }
    })
  }
}
</script>

<style scoped>
.file-upload-container { @apply space-y-4; }

.hidden-file-input {
  display: none;
}

.error-message {
  @apply flex items-center space-x-2 p-3 bg-red-100 border border-red-300 rounded-md text-red-800;
}

.uploaded-files {
  @apply border border-gray-200 rounded-lg p-4 bg-gray-50;
}

.uploaded-files-title {
  @apply flex items-center space-x-2 text-lg font-semibold text-gray-700 mb-3;
}

.file-list {
  @apply space-y-3;
}

.file-item {
  @apply flex items-center space-x-3 p-3 bg-white border border-gray-200 rounded-md;
}

.file-icon {
  @apply text-2xl;
}

.file-info {
  @apply flex-1 min-w-0;
}

.file-name {
  @apply block text-sm font-medium text-gray-900 truncate;
}

.file-size {
  @apply block text-xs text-gray-500;
}

.file-preview {
  @apply w-12 h-12 overflow-hidden rounded-md border border-gray-200;
}

.file-preview img {
  @apply w-full h-full object-cover;
}

.remove-file-button {
  @apply text-red-500 hover:text-red-700 transition-colors duration-150;
}

.remove-file-button:hover {
  @apply transform scale-110;
}
</style>
