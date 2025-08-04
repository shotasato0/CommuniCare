<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { handleImageChange } from "@/Utils/imageHandler";
import ImageModal from "./ImageModal.vue";

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

// 画像関連のref
const image = ref(null); // 画像ファイル
const imagePreview = ref(null); // 画像プレビュー
const isModalOpen = ref(false); // モーダル表示
const fileInput = ref(null); // ファイル選択ボタン

const localErrorMessage = ref(null); // エラーメッセージ

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

// 画像変更時の処理
const onImageChange = (event) => {
    handleImageChange(event, image, imagePreview, localErrorMessage);
};

// ファイル選択ボタンをクリックしたときの処理
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する
const removeImage = () => {
    image.value = null; // 画像を削除
    imagePreview.value = null; // 画像プレビューを削除
    localErrorMessage.value = null; // エラーメッセージを削除
    if (fileInput.value) {
        fileInput.value.value = ""; // ファイル入力の値をリセット
    }
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

    // 画像データが存在する場合、フォームデータに追加
    if (image.value) {
        formData.append("image", image.value);
    }

    // 投稿の送信
    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            // 投稿成功後の処理
            newPostTitle.value = ""; // タイトルをリセット
            newPostContent.value = ""; // 投稿内容をリセット
            image.value = null; // 画像をリセット
            imagePreview.value = null; // 画像プレビューをリセット
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
                ></textarea>

                <!-- 画像選択アイコン -->
                <div
                    class="absolute right-3 bottom-9 bg-gray-300 dark:bg-gray-600 text-black dark:text-gray-300 transition hover:bg-gray-400 dark:hover:bg-gray-500 hover:text-white rounded-md flex items-center justify-center cursor-pointer p-2"
                    style="width: 40px; height: 40px"
                    @click="triggerFileInput"
                    title="ファイルを選択"
                >
                    <i class="bi bi-card-image text-2xl"></i>
                </div>
            </div>

            <!-- 隠しファイル入力 -->
            <input
                type="file"
                accept="image/*"
                ref="fileInput"
                @change="onImageChange"
                style="display: none"
            />
            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 dark:text-red-400 mt-2">
                {{ localErrorMessage }}
            </div>
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

            <!-- 引用投稿の画像モーダル -->
            <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                <img
                    :src="imagePreview"
                    alt="投稿画像"
                    class="max-w-full max-h-full rounded-lg"
                />
            </ImageModal>
        </div>
    </div>
</template>

<style>
.link-hover:hover {
    opacity: 70%;
}
</style>
