<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue"; // ImageModalコンポーネントをインポート
import FileUpload from "./FileUpload.vue"; // 統一ファイル添付コンポーネント
import TextareaWithAttach from "./TextareaWithAttach.vue";
import DropOverlay from "./DropOverlay.vue";
import { useFileDragHover } from "@/Utils/useFileDragHover";
import { handleImageChange } from "@/Utils/imageHandler";

const props = defineProps({
    forumId: [String, Number], // String または Number 型を許容
});

const postData = ref({
    title: "",
    message: "",
    forum_id: props.forumId ? Number(props.forumId) : null, // forum_id を追加し、初期値を適切に設定
});

const image = ref(null); // 画像ファイル（レガシー対応）
const imagePreview = ref(null); // 画像プレビュー（レガシー対応）
const fileInput = ref(null); // ファイル入力（レガシー対応）
const isModalOpen = ref(false); // モーダル表示
const localErrorMessage = ref(null); // エラーメッセージ

// 統一ファイル添付システム用
const fileUploadRef = ref(null);
const attachedFiles = ref([]);
const useUnifiedUpload = ref(true); // 統一システムの使用フラグ

// ドラッグ&ドロップ（テキストエリア上限定）
const { isDragOver, onTextDragEnter, onTextDragOver, onTextDragLeave, onTextDrop, onTextMouseLeave } = useFileDragHover((files) => {
    if (fileUploadRef.value) {
        fileUploadRef.value.processExternalFiles(files);
    }
});

// forumIdの変更を監視し、postDataに反映
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// 画像ファイルのチェック（レガシー対応）
const onImageChange = (e) => {
    handleImageChange(e, image, imagePreview, localErrorMessage);
};

// ファイル選択ボタンをクリックしたときの処理（レガシー対応）
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する処理（レガシー対応）
const removeImage = () => {
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = ""; // ファイル入力の値をリセット
    }
};

// 統一ファイル添付システム用のハンドラー
const handleFilesChanged = (files) => {
    console.log('=== handleFilesChanged ===');
    console.log('受信ファイル数:', files.length);
    console.log('ファイル詳細:', files);
    attachedFiles.value = files;
};

const handleFileUploadError = (errorMessage) => {
    localErrorMessage.value = errorMessage;
};

// 投稿の送信処理（統一ファイル添付システム対応）
const submitPost = () => {
    if (!postData.value.forum_id || postData.value.forum_id === 0) {
        console.error("有効な掲示板IDが選択されていません。");
        return;
    }

    // デバッグ情報
    console.log('=== PostForm Debug ===');
    console.log('useUnifiedUpload:', useUnifiedUpload.value);
    console.log('attachedFiles.length:', attachedFiles.value.length);
    console.log('attachedFiles:', attachedFiles.value);
    console.log('image:', image.value);

    // フォームデータを作成
    const formData = new FormData();
    formData.append("title", postData.value.title);
    formData.append("message", postData.value.message);
    formData.append("forum_id", postData.value.forum_id);
    formData.append("_token", getCsrfToken());

    // 統一ファイル添付システムの使用
    if (useUnifiedUpload.value && attachedFiles.value.length > 0) {
        console.log('統一システムでファイル送信:', attachedFiles.value.length, 'files');
        attachedFiles.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
            console.log(`files[${index}]:`, file.name, file.size);
        });
    }
    // レガシー画像アップロード（後方互換性）
    else if (image.value) {
        console.log('レガシーシステムでファイル送信:', image.value.name);
        formData.append("image", image.value);
    } else {
        console.log('ファイルが選択されていません');
    }

    // 投稿の送信
    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            // 投稿成功後の処理
            resetForm();
            router.get(
                route("forum.index", { forum_id: postData.value.forum_id }),
                {
                    preserveState: true,
                }
            );
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
            localErrorMessage.value = "投稿に失敗しました。もう一度お試しください。";
        },
    });
};

// フォームリセット処理
const resetForm = () => {
    postData.value = {
        title: "",
        message: "",
        forum_id: props.forumId,
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
</script>

<template>
    <!-- 投稿フォーム -->
    <div class="bg-white dark:bg-gray-800 rounded-md mt-5 p-3">
        <form @submit.prevent="submitPost" enctype="multipart/form-data">
            <!-- 件名 -->
            <div class="flex mt-2">
                <p class="font-medium text-gray-900 dark:text-gray-100">件名</p>
                <input
                    v-model="postData.title"
                    class="border-gray-300 dark:border-gray-600 rounded-md px-2 ml-2 flex-auto bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                    type="text"
                    required
                    placeholder="件名を入力してください"
                />
            </div>

            <!-- 本文 -->
            <div class="flex flex-col mt-2">
                <p class="font-medium text-gray-900 dark:text-gray-100">本文</p>
                <div class="relative">
                    <TextareaWithAttach
                        v-model="postData.message"
                        :rows="3"
                        placeholder="本文を入力してください"
                        :textarea-class="'w-full p-2 pr-12 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                        button-title="ファイルを選択"
                        button-aria-label="ファイルを選択"
                        @attach-click="fileUploadRef?.openFileDialog()"
                        @dragenter="onTextDragEnter"
                        @dragover="onTextDragOver"
                        @dragleave="onTextDragLeave"
                        @drop="onTextDrop"
                        @mouseleave="onTextMouseLeave"
                    />
                    <DropOverlay v-if="isDragOver" />
                </div>
            </div>

            <!-- 統一ファイル添付システム -->
            <div class="mt-4">
                <FileUpload 
                    ref="fileUploadRef"
                    :visible="false"
                    @files-changed="handleFilesChanged"
                    @error="handleFileUploadError"
                />
            </div>

            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 dark:text-red-400 mt-2">
                {{ localErrorMessage }}
            </div>

            <!-- レガシー画像アップロード（後方互換性） -->
            <div v-if="!useUnifiedUpload" class="legacy-upload">
                <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">画像添付</p>
                
                <!-- 画像選択ボタン -->
                <button 
                    type="button"
                    @click="triggerFileInput"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <i class="bi bi-image mr-2"></i>
                    画像を選択
                </button>
                
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
                    <img
                        :src="imagePreview"
                        alt="画像プレビュー"
                        class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                        @click="isModalOpen = true"
                    />
                    <!-- 削除ボタン -->
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

                <!-- モーダル表示 -->
                <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                    <img
                        :src="imagePreview"
                        class="max-w-full max-h-full rounded-lg"
                    />
                </ImageModal>
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end mt-2">
                <button
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
