<template>
  <div v-if="attachments && attachments.length > 0" class="attachment-list">
    <h4 v-if="showTitle" class="attachment-title">
      <i class="bi bi-paperclip"></i>
      添付ファイル ({{ attachments.length }})
    </h4>
    
    <div class="attachment-grid">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        :class="[
          'attachment-item group',
          { 'attachment-image': attachment.file_type === 'image' }
        ]"
      >
        <!-- 画像プレビュー -->
        <div
          v-if="attachment.file_type === 'image'"
          class="attachment-image-preview"
          @click="openImageModal(attachment)"
        >
          <img
            :src="attachment.url"
            :alt="attachment.original_name"
            loading="lazy"
          />
          <div class="image-overlay">
            <i class="bi bi-zoom-in"></i>
          </div>
        </div>
        
        <!-- 非画像ファイル -->
        <div
          v-else
          class="attachment-file"
          @click="downloadFile(attachment)"
        >
          <!-- ファイルアイコン -->
          <div class="file-icon-large">
            <i :class="getFileIcon(attachment.file_type)"></i>
          </div>
          
          <!-- ファイル情報 -->
          <div class="file-info">
            <span class="file-name">{{ attachment.original_name }}</span>
            <span class="file-size">{{ formatFileSize(attachment.file_size) }}</span>
            <span class="file-type">{{ getFileTypeLabel(attachment.file_type) }}</span>
          </div>
          
          <!-- ダウンロードアイコン -->
          <div class="download-icon">
            <i class="bi bi-download"></i>
          </div>
        </div>
        
        <!-- 削除ボタン（権限がある場合のみ） -->
        <button
          v-if="canDelete"
          @click="deleteAttachment(attachment)"
          class="delete-attachment-button"
          :title="`${attachment.original_name}を削除`"
        >
          <i class="bi bi-x-circle-fill"></i>
        </button>
      </div>
    </div>
    
    <!-- 画像モーダル -->
    <div
      v-if="selectedImage"
      class="image-modal-overlay"
      @click="closeImageModal"
    >
      <div class="image-modal" @click.stop>
        <div class="image-modal-header">
          <h3 class="image-modal-title">{{ selectedImage.original_name }}</h3>
          <button @click="closeImageModal" class="image-modal-close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="image-modal-body">
          <img
            :src="selectedImage.url"
            :alt="selectedImage.original_name"
            class="modal-image"
          />
        </div>
        <div class="image-modal-footer">
          <span class="image-info">{{ formatFileSize(selectedImage.file_size) }}</span>
          <button 
            @click="downloadFile(selectedImage)"
            class="download-button"
          >
            <i class="bi bi-download"></i>
            ダウンロード
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AttachmentList',
  props: {
    attachments: {
      type: Array,
      default: () => []
    },
    showTitle: {
      type: Boolean,
      default: true
    },
    canDelete: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['delete-attachment'],
  
  data() {
    return {
      selectedImage: null
    }
  },
  
  methods: {
    getFileIcon(fileType) {
      const icons = {
        image: 'bi bi-file-earmark-image-fill text-blue-600',
        pdf: 'bi bi-file-earmark-pdf-fill text-red-600',
        document: 'bi bi-file-earmark-word-fill text-blue-800',
        excel: 'bi bi-file-earmark-excel-fill text-green-600',
        text: 'bi bi-file-earmark-text-fill text-gray-600'
      }
      
      return icons[fileType] || 'bi bi-file-earmark-fill text-gray-500'
    },
    
    getFileTypeLabel(fileType) {
      const labels = {
        image: '画像',
        pdf: 'PDF',
        document: 'Word文書',
        excel: 'Excel',
        text: 'テキスト'
      }
      
      return labels[fileType] || 'ファイル'
    },
    
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    
    openImageModal(attachment) {
      this.selectedImage = attachment
    },
    
    closeImageModal() {
      this.selectedImage = null
    },
    
    downloadFile(attachment) {
      // 新しいウィンドウでファイルを開く（ブラウザがダウンロードを処理）
      window.open(attachment.url, '_blank')
    },
    
    deleteAttachment(attachment) {
      if (confirm(`"${attachment.original_name}" を削除しますか？`)) {
        this.$emit('delete-attachment', attachment)
      }
    }
  }
}
</script>

<style scoped>
.attachment-list {
  @apply space-y-3;
}

.attachment-title {
  @apply flex items-center space-x-2 text-sm font-semibold text-gray-700;
}

.attachment-grid {
  @apply grid gap-3;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

.attachment-item {
  @apply relative;
}

.attachment-image .attachment-image-preview {
  @apply relative overflow-hidden rounded-lg border border-gray-200 cursor-pointer;
  aspect-ratio: 16/9;
}

.attachment-image-preview img {
  @apply w-full h-full object-cover transition-transform duration-200;
}

.attachment-image-preview:hover img {
  @apply scale-105;
}

.image-overlay {
  @apply absolute inset-0 bg-black bg-opacity-0 flex items-center justify-center text-white text-xl transition-all duration-200 opacity-0;
}

.attachment-image-preview:hover .image-overlay {
  @apply bg-opacity-30 opacity-100;
}

.attachment-file {
  @apply flex items-center space-x-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-150;
}

.file-icon-large {
  @apply text-3xl flex-shrink-0;
}

.file-info {
  @apply flex-1 min-w-0 space-y-1;
}

.file-name {
  @apply block text-sm font-medium text-gray-900 truncate;
}

.file-size,
.file-type {
  @apply block text-xs text-gray-500;
}

.download-icon {
  @apply text-gray-400 transition-colors duration-150;
}

.attachment-item:hover .download-icon {
  @apply text-blue-600;
}

.delete-attachment-button {
  @apply absolute -top-2 -right-2 text-red-500 hover:text-red-700 bg-white rounded-full shadow-md opacity-0 transition-all duration-150;
  z-index: 10;
}

.attachment-item:hover .delete-attachment-button {
  @apply opacity-100;
}

.delete-attachment-button:hover {
  @apply transform scale-110;
}

/* 画像モーダル */
.image-modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50;
}

.image-modal {
  @apply bg-white rounded-lg shadow-xl max-w-4xl max-h-[90vh] w-[90vw] overflow-hidden;
}

.image-modal-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200;
}

.image-modal-title {
  @apply text-lg font-semibold text-gray-900 truncate;
}

.image-modal-close {
  @apply text-gray-400 hover:text-gray-600 text-xl;
}

.image-modal-body {
  @apply p-4 max-h-[70vh] overflow-auto;
}

.modal-image {
  @apply w-full h-auto max-h-full object-contain;
}

.image-modal-footer {
  @apply flex items-center justify-between p-4 border-t border-gray-200 bg-gray-50;
}

.image-info {
  @apply text-sm text-gray-600;
}

.download-button {
  @apply flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-150;
}
</style>