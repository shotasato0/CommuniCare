<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue";
import AttachmentUploader from "./AttachmentUploader.vue";
import AttachmentList from "./AttachmentList.vue";

const props = defineProps({
    postId: {
        type: Number,
        required: true,
    },
    parentId: {
        type: Number,
        default: null,
    },
    selectedForumId: {
        type: Number,
        required: true,
    },
    replyToName: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "返信",
    },
});

const emit = defineEmits(["cancel"]);

const commentData = ref({
    post_id: props.postId,
    parent_id: props.parentId,
    message: "",
    replyToName: props.replyToName,
});

// 新Attachmentシステム
const attachments = ref([])
const showAttachmentUploader = ref(false)

// 旧システム互換性
const image = ref(null);
const imagePreview = ref(null);
const fileInput = ref(null);
const isModalOpen = ref(false);
const localErrorMessage = ref(null);

// システム選択（開発中のみ、本番では新システム固定）
const useNewAttachmentSystem = ref(true)

const placeholder = ref(`@${props.replyToName} さんへの返信を入力`);

// 旧システム：画像ファイルのチェック
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        localErrorMessage.value = "ファイルサイズが10MBを超えています。";
        return;
    }

    if (!file.type.startsWith('image/')) {
        localErrorMessage.value = "画像ファイルを選択してください。";
        return;
    }

    image.value = file;
    localErrorMessage.value = null;

    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const removeImage = () => {
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// 新システム：添付ファイル処理
const handleAttachmentSuccess = (newAttachments) => {
    console.log('コメント添付ファイルアップロード成功:', newAttachments)
}

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

// 送信可能かの判定
const canSubmit = computed(() => {
    return commentData.value.message.trim().length > 0
})

// コメント送信
const submitComment = () => {
    if (!canSubmit.value) {
        return;
    }

    const formData = new FormData();
    formData.append("post_id", commentData.value.post_id);
    formData.append("message", commentData.value.message);
    formData.append("_token", getCsrfToken());

    if (commentData.value.parent_id) {
        formData.append("parent_id", commentData.value.parent_id);
    }

    // 旧システム：画像データを追加
    if (!useNewAttachmentSystem.value && image.value) {
        formData.append("image", image.value);
    }

    // 新システム：添付ファイルIDを追加
    if (useNewAttachmentSystem.value && attachments.value.length > 0) {
        attachments.value.forEach((attachment, index) => {
            formData.append(`attachment_ids[${index}]`, attachment.id);
        });
    }

    router.post(route("comment.store"), formData, {
        onSuccess: () => {
            // フォームリセット
            commentData.value.message = "";
            image.value = null;
            imagePreview.value = null;
            attachments.value = [];
            showAttachmentUploader.value = false;

            // 掲示板をリロード
            router.get(
                route("forum.index", { forum_id: props.selectedForumId }),
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            );

            // フォームを閉じる
            emit("cancel");
        },
        onError: (errors) => {
            console.error("コメント送信に失敗しました:", errors);
        },
    });
};

// キャンセル
const cancelComment = () => {
    commentData.value.message = "";
    image.value = null;
    imagePreview.value = null;
    attachments.value = [];
    showAttachmentUploader.value = false;
    emit("cancel");
};

// アップローダー表示切り替え
const toggleAttachmentUploader = () => {
    showAttachmentUploader.value = !showAttachmentUploader.value;
}
</script>

<template>
    <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-4 border border-gray-200 dark:border-gray-600">
        <!-- フォームタイトル -->
        <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">
            {{ title }}
        </h4>

        <form @submit.prevent="submitComment" enctype="multipart/form-data">
            <!-- メッセージ入力 -->
            <div class="mb-4">
                <textarea
                    v-model="commentData.message"
                    class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-y"
                    :placeholder="placeholder"
                    rows="3"
                    required
                ></textarea>
            </div>

            <!-- 添付ファイル制御 -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="block font-medium text-gray-900 dark:text-gray-100 text-sm">
                        添付ファイル
                    </label>
                    
                    <!-- システム切り替え（開発時のみ） -->
                    <div class="flex items-center space-x-4">
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <label class="inline-flex items-center">
                                <input
                                    v-model="useNewAttachmentSystem"
                                    :value="true"
                                    type="radio"
                                    :name="`comment-attachment-system-${postId}-${parentId}`"
                                    class="form-radio text-blue-500 w-3 h-3"
                                />
                                <span class="ml-1">新</span>
                            </label>
                            <label class="inline-flex items-center ml-3">
                                <input
                                    v-model="useNewAttachmentSystem"
                                    :value="false"
                                    type="radio"
                                    :name="`comment-attachment-system-${postId}-${parentId}`"
                                    class="form-radio text-blue-500 w-3 h-3"
                                />
                                <span class="ml-1">旧</span>
                            </label>
                        </div>

                        <button
                            v-if="useNewAttachmentSystem"
                            type="button"
                            @click="toggleAttachmentUploader"
                            class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-700 transition-colors"
                        >
                            <i class="bi bi-paperclip mr-1"></i>
                            {{ showAttachmentUploader ? '閉じる' : '追加' }}
                        </button>
                    </div>
                </div>

                <!-- 新Attachmentシステム -->
                <div v-if="useNewAttachmentSystem" class="space-y-3">
                    <!-- アップローダー -->
                    <AttachmentUploader
                        v-if="showAttachmentUploader || attachments.length === 0"
                        v-model="attachments"
                        attachable-type="App\Models\Comment"
                        attachable-id="temp"
                        @upload-success="handleAttachmentSuccess"
                        :max-files="3"
                    />
                    
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

                <!-- 旧システム -->
                <div v-else class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors flex items-center space-x-2"
                        >
                            <i class="bi bi-card-image"></i>
                            <span>画像</span>
                        </button>

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            JPG, PNG, GIF（最大10MB）
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
                            class="w-24 h-24 object-cover rounded cursor-pointer hover:opacity-80 transition"
                            @click="isModalOpen = true"
                        />
                        <button
                            type="button"
                            @click="removeImage"
                            class="absolute -top-1 -right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs transition-colors"
                            title="削除"
                        >
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="flex justify-end space-x-2">
                <button
                    type="button"
                    @click="cancelComment"
                    class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors"
                >
                    キャンセル
                </button>
                
                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="px-4 py-2 text-sm bg-green-500 hover:bg-green-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-md transition-colors flex items-center space-x-1"
                >
                    <i class="bi bi-reply"></i>
                    <span>返信</span>
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
/* コンパクトなフォームスタイル */
textarea:focus {
    transition: all 0.2s ease-in-out;
}

/* 小さなラジオボタン */
input[type="radio"] {
    width: 0.75rem;
    height: 0.75rem;
}
</style>