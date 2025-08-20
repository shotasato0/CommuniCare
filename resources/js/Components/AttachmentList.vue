<script setup>
import { ref, computed } from 'vue'
import ImageModal from './ImageModal.vue'

const props = defineProps({
    attachments: {
        type: Array,
        default: () => []
    },
    showLegacyField: {
        type: Boolean,
        default: false
    },
    legacyImage: {
        type: String,
        default: null
    },
    canDelete: {
        type: Boolean,
        default: false
    },
    layout: {
        type: String,
        default: 'grid', // 'grid' | 'list'
        validator: (value) => ['grid', 'list'].includes(value)
    },
    size: {
        type: String,
        default: 'medium', // 'small' | 'medium' | 'large'
        validator: (value) => ['small', 'medium', 'large'].includes(value)
    }
})

const emit = defineEmits(['delete-attachment', 'download-attachment'])

// 画像モーダル
const isModalOpen = ref(false)
const currentImage = ref(null)

const openModal = (imageSrc) => {
    currentImage.value = imageSrc
    isModalOpen.value = true
}

// ファイルサイズのフォーマット
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// ファイルタイプ別アイコン
const getFileIcon = (fileType, fileName = '') => {
    const ext = fileName.split('.').pop()?.toLowerCase() || ''
    
    switch (fileType) {
        case 'image':
            return 'bi-file-image text-green-500'
        case 'pdf':
            return 'bi-file-pdf text-red-500'
        case 'document':
            return ext === 'doc' || ext === 'docx' ? 'bi-file-word text-blue-500' : 'bi-file-text text-gray-500'
        case 'excel':
            return 'bi-file-excel text-green-600'
        case 'text':
            return 'bi-file-text text-gray-600'
        case 'audio':
            return 'bi-file-music text-purple-500'
        default:
            return 'bi-file-earmark text-gray-400'
    }
}

// ファイル削除の確認
const confirmDelete = async (attachment) => {
    if (confirm(`「${attachment.original_name}」を削除してもよろしいですか？`)) {
        emit('delete-attachment', attachment.id)
    }
}

// ダウンロード処理
const downloadFile = (attachment) => {
    emit('download-attachment', attachment.id)
    
    // 直接ダウンロード
    const link = document.createElement('a')
    link.href = `/attachments/${attachment.id}/download`
    link.download = attachment.original_name
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

// 画像プレビュー用のURL生成
const getImageUrl = (attachment) => {
    return `/storage/${attachment.file_path}`
}

// レガシー画像URL
const getLegacyImageUrl = (imagePath) => {
    return imagePath?.startsWith('/storage/') ? imagePath : `/storage/${imagePath}`
}

// 画像ファイルの判定
const isImage = (attachment) => {
    return attachment.file_type === 'image'
}

// すべての添付ファイル（新旧統合）
const allAttachments = computed(() => {
    const result = [...(props.attachments || [])]
    
    // レガシー画像を表示する場合
    if (props.showLegacyField && props.legacyImage) {
        result.unshift({
            id: 'legacy',
            original_name: 'レガシー画像',
            file_type: 'image',
            file_size: 0,
            file_path: props.legacyImage,
            is_legacy: true
        })
    }
    
    return result
})

// レイアウト別のCSSクラス
const containerClass = computed(() => {
    const baseClass = 'attachments-container'
    
    if (props.layout === 'grid') {
        return `${baseClass} grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`
    }
    
    return `${baseClass} space-y-2`
})

// サイズ別のCSSクラス
const itemSizeClass = computed(() => {
    switch (props.size) {
        case 'small':
            return 'w-16 h-16'
        case 'large':
            return 'w-64 h-64'
        default: // medium
            return 'w-32 h-32'
    }
})
</script>

<template>
    <div>
        <div v-if="allAttachments.length > 0" :class="containerClass">
            <div 
                v-for="attachment in allAttachments" 
                :key="attachment.id"
                class="attachment-item"
                :class="{
                    'bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden': layout === 'grid',
                    'bg-gray-50 dark:bg-gray-700 rounded-md p-3': layout === 'list'
                }"
            >
                <!-- グリッドレイアウト -->
                <template v-if="layout === 'grid'">
                    <!-- 画像の場合 -->
                    <div v-if="isImage(attachment)" class="relative group">
                        <img 
                            :src="attachment.is_legacy ? getLegacyImageUrl(attachment.file_path) : getImageUrl(attachment)" 
                            :alt="attachment.original_name"
                            :class="itemSizeClass"
                            class="object-cover cursor-pointer hover:opacity-80 transition-opacity duration-300"
                            @click="openModal(attachment.is_legacy ? getLegacyImageUrl(attachment.file_path) : getImageUrl(attachment))"
                        />
                        
                        <!-- オーバーレイ -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex space-x-2">
                                <!-- ダウンロードボタン -->
                                <button
                                    v-if="!attachment.is_legacy"
                                    @click.stop="downloadFile(attachment)"
                                    class="bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-700 p-2 rounded-full transition-all"
                                    title="ダウンロード"
                                >
                                    <i class="bi bi-download text-sm"></i>
                                </button>
                                
                                <!-- 削除ボタン -->
                                <button
                                    v-if="canDelete && !attachment.is_legacy"
                                    @click.stop="confirmDelete(attachment)"
                                    class="bg-red-500 bg-opacity-80 hover:bg-opacity-100 text-white p-2 rounded-full transition-all"
                                    title="削除"
                                >
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- レガシーバッジ -->
                        <div v-if="attachment.is_legacy" class="absolute top-1 left-1">
                            <span class="bg-yellow-500 text-white text-xs px-1 py-0.5 rounded">
                                旧
                            </span>
                        </div>
                    </div>
                    
                    <!-- ファイルの場合 -->
                    <div v-else class="p-4 flex flex-col items-center justify-center h-full">
                        <i :class="getFileIcon(attachment.file_type, attachment.original_name)" class="text-4xl mb-2"></i>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center truncate w-full" :title="attachment.original_name">
                            {{ attachment.original_name }}
                        </p>
                        <p v-if="attachment.file_size" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ formatFileSize(attachment.file_size) }}
                        </p>
                        
                        <!-- ボタン -->
                        <div class="flex space-x-1 mt-3">
                            <button
                                @click="downloadFile(attachment)"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-xs transition-colors"
                                title="ダウンロード"
                            >
                                <i class="bi bi-download"></i>
                            </button>
                            
                            <button
                                v-if="canDelete"
                                @click="confirmDelete(attachment)"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-xs transition-colors"
                                title="削除"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- リストレイアウト -->
                <template v-else>
                    <div class="flex items-center space-x-3">
                        <!-- アイコンまたは画像サムネイル -->
                        <div class="flex-shrink-0">
                            <img 
                                v-if="isImage(attachment)"
                                :src="attachment.is_legacy ? getLegacyImageUrl(attachment.file_path) : getImageUrl(attachment)" 
                                :alt="attachment.original_name"
                                class="w-12 h-12 object-cover rounded cursor-pointer"
                                @click="openModal(attachment.is_legacy ? getLegacyImageUrl(attachment.file_path) : getImageUrl(attachment))"
                            />
                            <i 
                                v-else
                                :class="getFileIcon(attachment.file_type, attachment.original_name)" 
                                class="text-2xl"
                            ></i>
                        </div>
                        
                        <!-- ファイル情報 -->
                        <div class="flex-grow min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ attachment.original_name }}
                                <span v-if="attachment.is_legacy" class="ml-2 bg-yellow-500 text-white text-xs px-1 py-0.5 rounded">
                                    旧
                                </span>
                            </p>
                            <p v-if="attachment.file_size" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatFileSize(attachment.file_size) }}
                            </p>
                        </div>
                        
                        <!-- アクションボタン -->
                        <div class="flex space-x-2">
                            <button
                                v-if="!attachment.is_legacy"
                                @click="downloadFile(attachment)"
                                class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 p-1"
                                title="ダウンロード"
                            >
                                <i class="bi bi-download"></i>
                            </button>
                            
                            <button
                                v-if="canDelete && !attachment.is_legacy"
                                @click="confirmDelete(attachment)"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1"
                                title="削除"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- 添付ファイルがない場合 -->
        <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
            <i class="bi bi-inbox text-4xl mb-2"></i>
            <p>添付ファイルがありません</p>
        </div>

        <!-- 画像モーダル -->
        <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
            <img
                :src="currentImage"
                alt="添付画像"
                class="max-w-full max-h-full rounded-lg"
            />
        </ImageModal>
    </div>
</template>

<style scoped>
.attachment-item {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.attachment-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dark .attachment-item:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/* グリッドレイアウトでの画像アスペクト比維持 */
.attachment-item img {
    aspect-ratio: 1;
}
</style>