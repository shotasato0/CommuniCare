<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    forumId: [String, Number],
    title: {
        type: String,
        default: "投稿"
    }
});

const postData = ref({
    title: "",
    message: "",
    forum_id: props.forumId ? Number(props.forumId) : null,
});

// 統一ファイル添付システム
const selectedFiles = ref([]);
const fileInput = ref(null);
const uploading = ref(false);
const errors = ref([]);

// ファイル制限設定
const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv';

// forumIdの変更を監視
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// ファイル選択処理
const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    errors.value = [];

    // ファイル数チェック
    if (selectedFiles.value.length + files.length > MAX_FILES) {
        errors.value.push(`最大${MAX_FILES}ファイルまで選択可能です`);
        return;
    }

    // ファイルサイズ・形式チェック
    const validFiles = [];
    for (const file of files) {
        if (file.size > MAX_FILE_SIZE) {
            errors.value.push(`${file.name}: ファイルサイズが制限を超えています（最大10MB）`);
            continue;
        }
        validFiles.push(file);
    }

    selectedFiles.value = [...selectedFiles.value, ...validFiles];
};

// ファイル削除
const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

// ファイルアイコン取得
const getFileIcon = (filename) => {
    const ext = filename.split('.').pop().toLowerCase();
    
    const iconMap = {
        // 画像
        jpg: 'bi-file-image-fill text-blue-500', 
        jpeg: 'bi-file-image-fill text-blue-500', 
        png: 'bi-file-image-fill text-blue-500',
        gif: 'bi-file-image-fill text-blue-500',
        webp: 'bi-file-image-fill text-blue-500',
        // 文書
        pdf: 'bi-file-pdf-fill text-red-500',
        doc: 'bi-file-word-fill text-blue-600',
        docx: 'bi-file-word-fill text-blue-600',
        xls: 'bi-file-excel-fill text-green-600',
        xlsx: 'bi-file-excel-fill text-green-600',
        txt: 'bi-file-text-fill text-gray-600',
        csv: 'bi-file-spreadsheet-fill text-green-500'
    };
    
    return iconMap[ext] || 'bi-file-earmark-fill text-gray-500';
};

// ファイルサイズフォーマット
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// 画像プレビュー判定
const isImageFile = (file) => {
    return file.type.startsWith('image/');
};

// 画像プレビューURL生成
const getImagePreview = (file) => {
    if (isImageFile(file)) {
        return URL.createObjectURL(file);
    }
    return null;
};

// 送信可能判定
const canSubmit = computed(() => {
    return postData.value.title.trim() && 
           postData.value.message.trim() && 
           postData.value.forum_id &&
           !uploading.value;
});

// 投稿送信処理
const submitPost = async () => {
    if (!canSubmit.value) {
        console.error("必須項目が入力されていません。");
        return;
    }

    uploading.value = true;
    errors.value = [];

    try {
        const formData = new FormData();
        formData.append("title", postData.value.title);
        formData.append("message", postData.value.message);
        formData.append("forum_id", postData.value.forum_id);
        formData.append("_token", getCsrfToken());

        // ファイル添付
        selectedFiles.value.forEach((file) => {
            formData.append('files[]', file);
        });

        // 投稿送信
        router.post(route("forum.store"), formData, {
            onSuccess: () => {
                // フォームリセット
                postData.value = {
                    title: "",
                    message: "",
                    forum_id: props.forumId,
                };
                selectedFiles.value = [];
                if (fileInput.value) {
                    fileInput.value.value = "";
                }

                // リダイレクト
                router.get(
                    route("forum.index", { forum_id: props.forumId }),
                    { preserveState: true }
                );
            },
            onError: (errors) => {
                console.error("投稿に失敗しました:", errors);
                uploading.value = false;
            },
        });
    } catch (error) {
        console.error("投稿エラー:", error);
        errors.value.push("投稿の送信に失敗しました");
        uploading.value = false;
    }
};

// ファイル選択トリガー
const triggerFileInput = () => {
    fileInput.value.click();
};

// ドラッグ&ドロップ
const dragOver = ref(false);

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    dragOver.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    
    const files = Array.from(e.dataTransfer.files);
    if (fileInput.value) {
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        fileInput.value.files = dt.files;
        handleFileSelect({ target: { files: dt.files } });
    }
};
</script>

<template>
    <div class="bg-white dark:bg-slate-800 rounded-md mt-5 p-4">
        <!-- フォームタイトル -->
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            {{ title }}
        </h3>

        <form @submit.prevent="submitPost" enctype="multipart/form-data">
            <!-- 件名 -->
            <div class="mb-4">
                <label class="block font-medium text-gray-900 dark:text-gray-100 mb-2">
                    件名 <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="postData.title"
                    class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    type="text"
                    required
                    placeholder="件名を入力してください"
                />
            </div>

            <!-- 本文 -->
            <div class="mb-4">
                <label class="block font-medium text-gray-900 dark:text-gray-100 mb-2">
                    本文 <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <textarea
                        v-model="postData.message"
                        class="w-full p-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
                        required
                        placeholder="本文を入力してください"
                        rows="4"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                    ></textarea>
                    
                    <!-- 統一クリップアイコン -->
                    <button
                        type="button"
                        @click="triggerFileInput"
                        class="absolute right-3 bottom-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-md p-2 transition-colors"
                        title="ファイルを添付"
                        :disabled="uploading"
                    >
                        <i class="bi bi-paperclip text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- 隠しファイル入力 -->
            <input
                ref="fileInput"
                type="file"
                multiple
                :accept="ALLOWED_TYPES"
                @change="handleFileSelect"
                class="hidden"
            />

            <!-- エラー表示 -->
            <div v-if="errors.length > 0" class="mb-4">
                <div 
                    v-for="error in errors" 
                    :key="error" 
                    class="bg-red-100 dark:bg-red-900/20 border border-red-300 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-2 rounded-md mb-2"
                >
                    {{ error }}
                </div>
            </div>

            <!-- ファイルプレビュー -->
            <div v-if="selectedFiles.length > 0" class="mb-4">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    添付ファイル ({{ selectedFiles.length }})
                </h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="(file, index) in selectedFiles" 
                        :key="index"
                        class="relative bg-gray-50 dark:bg-slate-700 p-3 rounded-md border border-gray-200 dark:border-gray-600"
                    >
                        <!-- 画像プレビュー -->
                        <div v-if="isImageFile(file)" class="mb-2">
                            <img 
                                :src="getImagePreview(file)" 
                                :alt="file.name"
                                class="w-full h-24 object-cover rounded-md"
                            />
                        </div>
                        
                        <!-- ファイル情報 -->
                        <div class="flex items-start space-x-2">
                            <i :class="getFileIcon(file.name)" class="text-xl mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ file.name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatFileSize(file.size) }}
                                </p>
                            </div>
                            
                            <!-- 削除ボタン -->
                            <button
                                type="button"
                                @click="removeFile(index)"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1"
                                title="削除"
                            >
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ドラッグ&ドロップエリア -->
                <div 
                    v-if="selectedFiles.length < MAX_FILES"
                    class="mt-3 border-2 border-dashed rounded-lg p-4 text-center cursor-pointer"
                    :class="[
                        dragOver 
                            ? 'border-blue-300 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' 
                            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                    ]"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    @drop="handleDrop"
                    @click="triggerFileInput"
                >
                    <i class="bi bi-plus-circle text-2xl text-gray-400 dark:text-gray-500 mb-2"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        ファイルをドラッグ&ドロップまたはクリックして追加
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        最大{{ MAX_FILES }}ファイル、各ファイル最大10MB
                    </p>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-md transition-colors flex items-center space-x-2"
                >
                    <span v-if="uploading" class="animate-spin">
                        <i class="bi bi-arrow-clockwise"></i>
                    </span>
                    <i v-else class="bi bi-send"></i>
                    <span>{{ uploading ? '投稿中...' : '投稿する' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
/* ドラッグ&ドロップ時のアニメーション */
.transition-colors {
    transition: all 0.2s ease-in-out;
}

/* フォーカス時のアニメーション */
input:focus, textarea:focus {
    transition: all 0.2s ease-in-out;
}
</style>