<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    attachableType: {
        type: String,
        required: true
    },
    attachableId: {
        type: [Number, String],
        default: null
    },
    accept: {
        type: String,
        default: 'image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.rtf,audio/*'
    },
    maxFiles: {
        type: Number,
        default: 10
    },
    maxFileSize: {
        type: Number,
        default: 10 * 1024 * 1024 // 10MB
    },
    maxTotalSize: {
        type: Number,
        default: 100 * 1024 * 1024 // 100MB
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'upload-success', 'upload-error'])

const fileInput = ref(null)
const selectedFiles = ref([])
const uploading = ref(false)
const errors = ref([])

// ファイル選択時の処理
const handleFileSelect = (event) => {
    const files = Array.from(event.target.files)
    errors.value = []

    // ファイル数チェック
    if (files.length > props.maxFiles) {
        errors.value.push(`最大${props.maxFiles}ファイルまで選択可能です`)
        return
    }

    // ファイルサイズチェック
    let totalSize = 0
    const validFiles = []

    for (const file of files) {
        if (file.size > props.maxFileSize) {
            errors.value.push(`${file.name}: ファイルサイズが制限を超えています（最大10MB）`)
            continue
        }
        totalSize += file.size
        validFiles.push(file)
    }

    // 総ファイルサイズチェック
    if (totalSize > props.maxTotalSize) {
        errors.value.push(`ファイル合計サイズが制限を超えています（最大100MB）`)
        return
    }

    selectedFiles.value = validFiles
}

// ファイルアップロード処理
const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) {
        return
    }

    // attachable_idがnullの場合は、一時的にダミーIDを使用
    const attachableId = props.attachableId || 'temp'

    uploading.value = true
    errors.value = []

    try {
        const formData = new FormData()
        
        // ファイルを追加
        selectedFiles.value.forEach((file) => {
            formData.append('files[]', file)
        })
        
        // 関連モデル情報を追加
        formData.append('attachable_type', props.attachableType)
        formData.append('attachable_id', attachableId)

        const response = await fetch('/api/attachments', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })

        const data = await response.json()

        if (data.success) {
            console.log('Upload successful, attachments:', data.attachments);
            emit('upload-success', data.attachments)
            emit('update:modelValue', [...props.modelValue, ...data.attachments])
            
            // フォームをクリア
            selectedFiles.value = []
            if (fileInput.value) {
                fileInput.value.value = ''
            }
        } else {
            errors.value.push(data.message || 'アップロードに失敗しました')
            emit('upload-error', data)
        }
    } catch (error) {
        console.error('Upload error:', error)
        errors.value.push('ネットワークエラーが発生しました')
        emit('upload-error', error)
    } finally {
        uploading.value = false
    }
}

// ファイル削除処理
const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
}

// ファイルサイズのフォーマット
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// ファイルアイコンの取得
const getFileIcon = (filename) => {
    const ext = filename.split('.').pop().toLowerCase()
    
    const iconMap = {
        // 画像
        jpg: 'bi-file-image', jpeg: 'bi-file-image', png: 'bi-file-image', 
        gif: 'bi-file-image', webp: 'bi-file-image', bmp: 'bi-file-image',
        // 文書
        pdf: 'bi-file-pdf', doc: 'bi-file-word', docx: 'bi-file-word',
        xls: 'bi-file-excel', xlsx: 'bi-file-excel',
        txt: 'bi-file-text', csv: 'bi-file-spreadsheet', rtf: 'bi-file-richtext',
        // 音声
        mp3: 'bi-file-music', wav: 'bi-file-music', ogg: 'bi-file-music', m4a: 'bi-file-music'
    }
    
    return iconMap[ext] || 'bi-file-earmark'
}

// 選択可能な状態の計算
const canUpload = computed(() => {
    return selectedFiles.value.length > 0 && !uploading.value && props.attachableId
})

// ドラッグ&ドロップ対応
const dragOver = ref(false)

const handleDragOver = (e) => {
    e.preventDefault()
    dragOver.value = true
}

const handleDragLeave = (e) => {
    e.preventDefault()
    dragOver.value = false
}

const handleDrop = (e) => {
    e.preventDefault()
    dragOver.value = false
    
    const files = Array.from(e.dataTransfer.files)
    if (fileInput.value) {
        // FileListを模擬的に作成
        const dt = new DataTransfer()
        files.forEach(file => dt.items.add(file))
        fileInput.value.files = dt.files
        handleFileSelect({ target: { files: dt.files } })
    }
}
</script>

<template>
    <div class="attachment-uploader">
        <!-- ファイル選択エリア -->
        <div 
            class="upload-area border-2 border-dashed rounded-lg p-4 sm:p-6 text-center transition-colors"
            :class="{
                'border-blue-300 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20': dragOver,
                'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500': !dragOver,
                'opacity-50 cursor-not-allowed': disabled
            }"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                :accept="accept"
                :disabled="disabled"
                @change="handleFileSelect"
                class="hidden"
            />
            
            <div v-if="!disabled">
                <i class="bi bi-cloud-upload text-3xl sm:text-4xl text-gray-400 dark:text-gray-500 mb-2"></i>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300 mb-2">
                    ファイルをドラッグ&ドロップするか、クリックして選択
                </p>
                <button
                    type="button"
                    @click="fileInput?.click()"
                    class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
                >
                    ファイルを選択
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    最大{{ maxFiles }}ファイル、各ファイル最大{{ formatFileSize(maxFileSize) }}、
                    合計最大{{ formatFileSize(maxTotalSize) }}
                </p>
            </div>
            
            <div v-else class="text-gray-400 dark:text-gray-500">
                <i class="bi bi-lock text-2xl mb-2"></i>
                <p>アップロード無効</p>
            </div>
        </div>

        <!-- エラー表示 -->
        <div v-if="errors.length > 0" class="mt-4">
            <div 
                v-for="error in errors" 
                :key="error" 
                class="bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-2 rounded-md mb-2"
            >
                {{ error }}
            </div>
        </div>

        <!-- 選択されたファイル一覧 -->
        <div v-if="selectedFiles.length > 0" class="mt-4">
            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                選択されたファイル ({{ selectedFiles.length }})
            </h4>
            
            <div class="space-y-2 max-h-48 overflow-y-auto">
                <div 
                    v-for="(file, index) in selectedFiles" 
                    :key="index"
                    class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-md"
                >
                    <div class="flex items-center space-x-3">
                        <i :class="getFileIcon(file.name)" class="text-xl text-gray-600 dark:text-gray-300"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ file.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatFileSize(file.size) }}
                            </p>
                        </div>
                    </div>
                    
                    <button
                        type="button"
                        @click="removeFile(index)"
                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1"
                        title="削除"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            <!-- アップロードボタン -->
            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    @click="uploadFiles"
                    :disabled="!canUpload"
                    class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
                >
                    <span v-if="uploading" class="animate-spin">
                        <i class="bi bi-arrow-clockwise"></i>
                    </span>
                    <i v-else class="bi bi-upload"></i>
                    <span>{{ uploading ? 'アップロード中...' : 'アップロード' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.upload-area {
    min-height: 120px;
    cursor: pointer;
}

.upload-area:hover:not(.opacity-50) {
    background-color: rgba(249, 250, 251, 0.5);
}

.dark .upload-area:hover:not(.opacity-50) {
    background-color: rgba(31, 41, 55, 0.5);
}
</style>