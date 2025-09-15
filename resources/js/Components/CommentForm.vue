<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue";
import FileUpload from "./FileUpload.vue"; // 統一ファイル添付コンポーネント
import { handleImageChange } from "@/Utils/imageHandler";

// コメントフォームのプロパティを定義
const props = defineProps({
    postId: {
        // 投稿ID
        type: Number,
        required: true,
    },
    parentId: {
        // 親コメントID
        type: Number,
        default: null,
    },
    selectedForumId: {
        // 選択中の掲示版ID
        type: Number,
        required: true,
    },
    replyToName: {
        // 返信先のユーザー名
        type: String,
        default: "",
    },
    title: {
        // フォームのタイトル
        type: String,
        default: "返信",
    },
});

const emit = defineEmits(["cancel"]); // キャンセルイベントを発行するためのemit
const placeholder = ref(`@${props.replyToName} さんへの返信を入力`); // プレースホルダー

// コメントデータを管理するref
const commentData = ref({
    // コメントデータ
    post_id: props.postId, // 投稿ID
    parent_id: props.parentId, // 親コメントID
    message: "", // コメントメッセージ
    replyToName: props.replyToName, // 返信先のユーザー名
});

// 画像関連のrefを追加（レガシー対応）
const image = ref(null); // 画像ファイル
const imagePreview = ref(null); // 画像プレビュー
const fileInput = ref(null); // ファイル選択ボタン
const isModalOpen = ref(false); // モーダル表示
const localErrorMessage = ref(null); // エラーメッセージ

// 統一ファイル添付システム用
const fileUploadRef = ref(null);
const attachedFiles = ref([]);
const useUnifiedUpload = ref(true); // 統一システムの使用フラグ

// クリップアイコンクリックでファイルダイアログを開く
// アイコンから直接 fileUploadRef を呼び出すため追加の関数は不要

// コンポーネントのマウント時に初期値を設定
onMounted(() => {
    // コメントに対する返信の場合のみメンションを追加
    if (props.replyToName && props.parentId) {
        commentData.value.message = `@${props.replyToName} `;
    }
});

// 画像ファイルのチェック
const onImageChange = (event) => {
    handleImageChange(event, image, imagePreview, localErrorMessage);
};

// ファイル選択ボタンをクリックしたときの処理
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する処理（レガシー対応）
const removeImage = () => {
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// 統一ファイル添付システム用のハンドラー
const handleFilesChanged = (files) => {
    attachedFiles.value = files;
};

const handleFileUploadError = (errorMessage) => {
    localErrorMessage.value = errorMessage;
};

// コメントの送信処理（統一ファイル添付システム対応）
const submitComment = () => {
    const token = getCsrfToken();

    // フォームデータを作成
    const formData = new FormData();
    formData.append("message", commentData.value.message);
    formData.append("post_id", props.postId);
    formData.append("_token", token);

    // 親コメントIDが存在する場合はフォームデータに追加
    if (props.parentId) {
        formData.append("parent_id", props.parentId);
    }

    // 統一ファイル添付システムの使用
    if (useUnifiedUpload.value && attachedFiles.value.length > 0) {
        attachedFiles.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
    }
    // レガシー画像アップロード（後方互換性）
    else if (image.value) {
        formData.append("image", image.value);
    }

    // コメントの投稿処理を実行
    router.post(route("comment.store", { post: props.postId }), formData, {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            router.visit(
                route("forum.index", { forum_id: props.selectedForumId }),
                {
                    preserveScroll: true,
                    replace: true,
                }
            );
        },
        onError: (errors) => {
            console.error("コメントの投稿に失敗しました:", errors);
            localErrorMessage.value = "コメントの投稿に失敗しました。もう一度お試しください。";
        },
    });
};

// フォームリセット処理
const resetForm = () => {
    commentData.value = {
        post_id: props.postId,
        parent_id: props.parentId,
        message: props.replyToName && props.parentId ? `@${props.replyToName} ` : "",
        replyToName: props.replyToName,
    };
    
    // 統一ファイル添付システムのリセット
    if (fileUploadRef.value) {
        fileUploadRef.value.reset();
    }
    attachedFiles.value = [];
    
    // レガシー画像システムのリセット
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
    
    localErrorMessage.value = null;
};

// キャンセルハンドラーを追加
const handleCancel = () => {
    // フォームをリセット
    commentData.value = {
        post_id: props.postId, // 投稿IDをリセット
        parent_id: props.parentId, // 親コメントIDをリセット
        message: "", // コメントメッセージをリセット
        replyToName: props.replyToName, // 返信先のユーザー名をリセット
    };
    // 親コンポーネントにキャンセルイベントを発行
    emit("cancel"); // キャンセルイベントを発行
};
</script>

<template>
    <div class="mt-4">
        <!-- コメントフォーム -->
        <form @submit.prevent="submitComment" enctype="multipart/form-data">
            <div>
                <!-- コメントメッセージ入力欄（クリップアイコン付き） -->
                <div class="relative">
                    <textarea
                        v-model="commentData.message"
                        class="w-full p-2 pr-12 pb-12 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        required
                        :placeholder="placeholder"
                        rows="3"
                    ></textarea>
                    <button
                        type="button"
                        class="absolute right-3 bottom-9 bg-gray-300 dark:bg-gray-600 text-black dark:text-gray-300 transition hover:bg-gray-400 dark:hover:bg-gray-500 rounded-md flex items-center justify-center cursor-pointer p-2"
                        style="width: 40px; height: 40px"
                        @click="fileUploadRef?.openFileDialog()"
                        title="ファイルを選択"
                    >
                        <i class="bi bi-paperclip text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- 統一ファイル添付システム -->
            <div class="mt-3">
                <FileUpload 
                    ref="fileUploadRef"
                    @files-changed="handleFilesChanged"
                    @error="handleFileUploadError"
                />
            </div>

            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 dark:text-red-400 mt-2">
                {{ localErrorMessage }}
            </div>

            <!-- レガシー画像アップロード（後方互換性・非表示） -->
            <div v-if="!useUnifiedUpload" class="legacy-upload" style="display: none;">
                <!-- 隠しファイル入力 -->
                <input
                    type="file"
                    accept="image/*"
                    ref="fileInput"
                    @change="onImageChange"
                    style="display: none"
                />
                
                <!-- プレビュー表示 -->
                <div v-if="imagePreview" class="relative mt-2 inline-block">
                    <!-- プレビュー画像 -->
                    <img
                        :src="imagePreview"
                        alt="画像プレビュー"
                        class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                        @click="isModalOpen = true"
                    />
                    <!-- プレビュー画像削除ボタン -->
                    <div
                        class="absolute top-0 right-0 bg-white dark:bg-gray-800 rounded-full p-1 cursor-pointer flex items-center justify-center"
                        @click="removeImage"
                        title="画像を削除"
                        style="width: 24px; height: 24px"
                    >
                        <i
                            class="bi bi-x-circle text-black dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-400"
                        ></i>
                    </div>
                </div>

                <!-- コメント画像モーダル -->
                <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                    <img
                        :src="imagePreview"
                        class="max-w-full max-h-full rounded-lg"
                    />
                </ImageModal>
            </div>

            <!-- コメントフォームボタン群 -->
            <div class="flex justify-end space-x-2 mt-2">
                <!-- キャンセルボタン -->
                <button
                    type="button"
                    @click="handleCancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium transition hover:bg-gray-500 dark:hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- コメント送信ボタン -->
                <button
                    type="submit"
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
