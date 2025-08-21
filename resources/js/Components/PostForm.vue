<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue";
import AttachmentUploader from "./AttachmentUploader.vue";
import AttachmentList from "./AttachmentList.vue";

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

// 新Attachmentシステム
const attachments = ref([])
const showAttachmentUploader = ref(false)

// 旧システム互換性（段階的移行用）
const image = ref(null);
const imagePreview = ref(null);
const fileInput = ref(null);
const isModalOpen = ref(false);
const localErrorMessage = ref(null);

// システム選択（開発中は選択可能、本番では新システム固定）
const useNewAttachmentSystem = ref(true) // 本番では true に固定

// forumIdの変更を監視し、postDataに反映
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// 旧システム：画像ファイルのチェック
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    // ファイルサイズチェック
    if (file.size > 10 * 1024 * 1024) { // 10MB
        localErrorMessage.value = "ファイルサイズが10MBを超えています。";
        return;
    }

    // 画像ファイルチェック
    if (!file.type.startsWith('image/')) {
        localErrorMessage.value = "画像ファイルを選択してください。";
        return;
    }

    image.value = file;
    localErrorMessage.value = null;

    // プレビュー生成
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// 旧システム：ファイル選択ボタンをクリック
const triggerFileInput = () => {
    fileInput.value.click();
};

// 旧システム：画像を削除する処理
const removeImage = () => {
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// 新システム：添付ファイル成功時の処理
const handleAttachmentSuccess = (newAttachments) => {
    console.log('ファイルアップロード成功:', newAttachments)
}

// 新システム：添付ファイル削除
const handleAttachmentDelete = async (attachmentId) => {
    try {
        const response = await fetch(`/api/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })

        const data = await response.json()
        
        if (data.success) {
            attachments.value = attachments.value.filter(a => a.id !== attachmentId)
        } else {
            console.error('削除失敗:', data.message)
        }
    } catch (error) {
        console.error('削除エラー:', error)
    }
}

// フォーム送信可能かの判定
const canSubmit = computed(() => {
    return postData.value.title.trim() && 
           postData.value.message.trim() && 
           postData.value.forum_id
})

// 投稿の送信処理
const submitPost = () => {
    if (!canSubmit.value) {
        console.error("必須項目が入力されていません。");
        return;
    }

    const formData = new FormData();
    formData.append("title", postData.value.title);
    formData.append("message", postData.value.message);
    formData.append("forum_id", postData.value.forum_id);
    formData.append("_token", getCsrfToken());

    // 旧システム：画像データを追加
    if (!useNewAttachmentSystem.value && image.value) {
        formData.append("image", image.value);
    }

    // 新システム：添付ファイルIDを追加
    if (useNewAttachmentSystem.value && attachments.value.length > 0) {
        console.log('Adding attachments to post:', attachments.value);
        attachments.value.forEach((attachment, index) => {
            formData.append(`attachment_ids[${index}]`, attachment.id);
            console.log(`Added attachment_ids[${index}]:`, attachment.id);
        });
    } else {
        console.log('No attachments to add. useNewAttachmentSystem:', useNewAttachmentSystem.value, 'attachments.length:', attachments.value.length);
    }

    // 投稿送信
    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            // フォームリセット
            postData.value = {
                title: "",
                message: "",
                forum_id: props.forumId,
            };
            
            // 旧システムリセット
            image.value = null;
            imagePreview.value = null;
            
            // 新システムリセット
            attachments.value = [];
            showAttachmentUploader.value = false;

            // リダイレクト
            router.get(
                route("forum.index", { forum_id: postData.value.forum_id }),
                { preserveState: true }
            );
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};

// アップローダーの表示切り替え
const toggleAttachmentUploader = () => {
    showAttachmentUploader.value = !showAttachmentUploader.value;
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-md mt-5 p-4">
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
                    class="w-full border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                <textarea
                    v-model="postData.message"
                    class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
                    required
                    placeholder="本文を入力してください"
                    rows="4"
                ></textarea>
            </div>

            <!-- 添付ファイル制御 -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="block font-medium text-gray-900 dark:text-gray-100">
                        添付ファイル
                    </label>
                    
                    <!-- システム切り替え（開発時のみ表示） -->
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <label class="inline-flex items-center">
                                <input
                                    v-model="useNewAttachmentSystem"
                                    :value="true"
                                    type="radio"
                                    name="attachment-system"
                                    class="form-radio text-blue-500"
                                />
                                <span class="ml-2">新システム</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input
                                    v-model="useNewAttachmentSystem"
                                    :value="false"
                                    type="radio"
                                    name="attachment-system"
                                    class="form-radio text-blue-500"
                                />
                                <span class="ml-2">旧システム</span>
                            </label>
                        </div>

                        <button
                            v-if="useNewAttachmentSystem"
                            type="button"
                            @click="toggleAttachmentUploader"
                            class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
                        >
                            <i class="bi bi-paperclip mr-1"></i>
                            {{ showAttachmentUploader ? '閉じる' : 'ファイル追加' }}
                        </button>
                    </div>
                </div>

                <!-- 新Attachmentシステム -->
                <div v-if="useNewAttachmentSystem" class="space-y-4">
                    <!-- アップローダー -->
                    <AttachmentUploader
                        v-if="showAttachmentUploader || attachments.length === 0"
                        v-model="attachments"
                        attachable-type="App\Models\Post"
                        attachable-id="temp"
                        @upload-success="handleAttachmentSuccess"
                        :disabled="!postData.forum_id"
                    />
                    
                    <!-- 注意メッセージ -->
                    <div v-if="!postData.forum_id" class="text-sm text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-md">
                        <i class="bi bi-exclamation-triangle mr-2"></i>
                        掲示板を選択してからファイルをアップロードしてください
                    </div>

                    <!-- 添付ファイル一覧 -->
                    <AttachmentList
                        v-if="attachments.length > 0"
                        :attachments="attachments"
                        :can-delete="true"
                        layout="list"
                        size="small"
                        @delete-attachment="handleAttachmentDelete"
                    />
                </div>

                <!-- 旧システム（互換性維持） -->
                <div v-else class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <!-- ファイル選択ボタン -->
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center space-x-2"
                        >
                            <i class="bi bi-card-image"></i>
                            <span>画像を選択</span>
                        </button>

                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            JPG, PNG, GIF対応（最大10MB）
                        </div>
                    </div>

                    <!-- 隠しファイル入力 -->
                    <input
                        type="file"
                        accept="image/*"
                        ref="fileInput"
                        @change="onImageChange"
                        class="hidden"
                    />

                    <!-- エラーメッセージ -->
                    <div v-if="localErrorMessage" class="text-red-500 dark:text-red-400 text-sm">
                        {{ localErrorMessage }}
                    </div>

                    <!-- プレビュー表示 -->
                    <div v-if="imagePreview" class="relative inline-block">
                        <img
                            :src="imagePreview"
                            alt="画像プレビュー"
                            class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                            @click="isModalOpen = true"
                        />
                        <!-- 削除ボタン -->
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm transition-colors"
                            title="画像を削除"
                        >
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-md transition-colors flex items-center space-x-2"
                >
                    <i class="bi bi-send"></i>
                    <span>投稿する</span>
                </button>
            </div>
        </form>

        <!-- 画像モーダル（旧システム用） -->
        <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
            <img
                :src="imagePreview"
                alt="画像プレビュー"
                class="max-w-full max-h-full rounded-lg"
            />
        </ImageModal>
    </div>
</template>

<style scoped>
/* フォーカス時のアニメーション */
input:focus, textarea:focus {
    transition: all 0.2s ease-in-out;
}

/* ラジオボタンのスタイル調整 */
input[type="radio"] {
    width: 1rem;
    height: 1rem;
}
</style>