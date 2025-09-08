<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import FileUpload from "./FileUpload.vue";

// 引用付き投稿フォームのprops
const props = defineProps({
    show: Boolean, // フォームの表示
    quotedPost: Object, // 引用元の投稿
    forumId: Number, // 論壇ID
});

// 引用付き投稿フォームのemit
const emit = defineEmits(["close"]); // フォームを閉じる

// 新しい投稿の内容
const newPostContent = ref(""); // 投稿の内容
const newPostTitle = ref(""); // 投稿のタイトル

// 統一ファイル添付システム
const fileUploadRef = ref(null);
const attachedFiles = ref([]);
const localErrorMessage = ref(null);

// D&D UIをテキストエリア上のみ表示するための状態
const isDragOverTextbox = ref(false);
const textAreaRef = ref(null);
let dragDepth = 0;
let _preventIfFiles;

const hasFiles = (e) => {
    const types = e?.dataTransfer?.types;
    return types && (types.includes && types.includes('Files'));
};

const onTextDragEnter = (e) => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    dragDepth++;
    isDragOverTextbox.value = true;
};

const onTextDragOver = (e) => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    isDragOverTextbox.value = true;
};

const onTextDragLeave = (e) => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    dragDepth = Math.max(0, dragDepth - 1);
    if (dragDepth === 0) {
        isDragOverTextbox.value = false;
    }
};

const onTextDrop = (e) => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    dragDepth = 0;
    isDragOverTextbox.value = false;
    const files = e.dataTransfer?.files || [];
    if (fileUploadRef.value && files.length > 0) {
        fileUploadRef.value.processExternalFiles(files);
    }
};

onMounted(() => {
    _preventIfFiles = (e) => {
        if (hasFiles(e)) {
            e.preventDefault();
        }
    };
    window.addEventListener('dragover', _preventIfFiles);
    window.addEventListener('drop', _preventIfFiles);
});

onUnmounted(() => {
    if (_preventIfFiles) {
        window.removeEventListener('dragover', _preventIfFiles);
        window.removeEventListener('drop', _preventIfFiles);
    }
});

// CSRFトークンを取得する関数
function getCsrfToken() {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    if (!token) {
        console.error("CSRFトークンが見つかりません。");
    }
    return token;
}

// FileUploadイベント
const handleFilesChanged = (files) => {
    attachedFiles.value = files;
};
const handleFileUploadError = (msg) => {
    localErrorMessage.value = msg;
};

// キャンセルボタン
const cancel = () => {
    emit("close"); // フォームを閉じる
};

// 引用付き投稿を送信
const submitQuotePost = () => {
    // 掲示板IDが選択されていない場合はエラー
    if (!props.forumId || props.forumId === 0) {
        console.error("有効な掲示板IDが選択されていません。");
        return;
    }

    // フォームデータを作成
    const formData = new FormData();
    formData.append("title", newPostTitle.value || ""); // タイトルが空の場合は空文字を設定
    formData.append("message", newPostContent.value); // 投稿の内容を追加
    formData.append("forum_id", props.forumId); // Forum IDを追加
    formData.append("quoted_post_id", props.quotedPost.id); // 引用元の投稿IDを追加
    formData.append("_token", getCsrfToken()); // CSRFトークンを追加

    // 添付ファイル（統一システム）
    if (attachedFiles.value.length > 0) {
        attachedFiles.value.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
    }

    // 投稿の送信
    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            // 投稿成功後の処理
            newPostTitle.value = "";
            newPostContent.value = "";
            if (fileUploadRef.value) fileUploadRef.value.reset();
            attachedFiles.value = [];
            // 掲示板ページにリダイレクト
            router.get(route("forum.index", { forum_id: props.forumId }), {
                preserveState: true, // ページの状態を保存
            });
            emit("close"); // フォームを閉じる
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};

// 引用投稿元のプレビュー表示時の最大表示文字数
const maxPreviewLength = 100;

// 引用投稿元のプレビュー表示時の最大表示文字数を超えた場合、省略表示
const truncatedMessage = computed(() => {
    return props.quotedPost?.message.length > maxPreviewLength
        ? props.quotedPost.message.slice(0, maxPreviewLength) + "..."
        : props.quotedPost.message;
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center bg-black/50 dark:bg-black/70 z-50"
    >
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md max-w-md w-full">
            <!-- 引用元の投稿表示 -->
            <div class="border border-gray-300 dark:border-gray-600 p-2 rounded-lg mb-4 bg-gray-100 dark:bg-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    引用元の投稿
                </h3>
                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                    {{ truncatedMessage }}
                </p>
            </div>

            <!-- 新しい投稿の入力フォーム -->
            <div class="relative">
                <textarea
                    v-model="newPostContent"
                    class="w-full p-2 pr-12 border border-gray-300 dark:border-gray-600 rounded mb-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                    placeholder="投稿文を入力してください"
                    rows="4"
                    ref="textAreaRef"
                    @dragenter="onTextDragEnter"
                    @dragover="onTextDragOver"
                    @dragleave="onTextDragLeave"
                    @drop="onTextDrop"
                ></textarea>
                <!-- 小さな添付ボタン（クリックで選択） -->
                <button
                    type="button"
                    class="absolute right-3 bottom-9 bg-gray-300 dark:bg-gray-600 text-black dark:text-gray-300 transition hover:bg-gray-400 dark:hover:bg-gray-500 rounded-md flex items-center justify-center cursor-pointer p-2"
                    style="width: 40px; height: 40px"
                    @click="fileUploadRef?.openFileDialog()"
                    title="ファイルを選択"
                >
                    <i class="bi bi-paperclip text-2xl"></i>
                </button>

                <!-- ドラッグ中のみ表示するオーバーレイのD&D UI -->
                <div v-if="isDragOverTextbox" class="absolute inset-0 z-10 flex items-center justify-center">
                    <div class="w-full h-full border-2 border-dashed border-blue-400 bg-blue-50/70 dark:bg-blue-900/30 rounded-md flex items-center justify-center">
                        <div class="text-center text-blue-700 dark:text-blue-200">
                            <i class="bi bi-paperclip text-3xl mb-2 block"></i>
                            <p>ここにファイルをドロップして添付</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 dark:text-red-400 mt-2">
                {{ localErrorMessage }}
            </div>

            <!-- 統一ファイル添付（ドロップUIは非表示、一覧・削除のみ） -->
            <div class="mt-2">
                <FileUpload
                    ref="fileUploadRef"
                    :showDropZone="false"
                    @files-changed="handleFilesChanged"
                    @error="handleFileUploadError"
                />
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end space-x-2">
                <button
                    @click="cancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium transition hover:bg-gray-500 dark:hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
                <button
                    @click="submitQuotePost"
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>

            
        </div>
    </div>
</template>

<style>
.link-hover:hover {
    opacity: 70%;
}
</style>
