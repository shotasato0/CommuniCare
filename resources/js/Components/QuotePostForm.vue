<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { handleImageChange } from "@/Utils/imageHandler";
import ImageModal from "./ImageModal.vue";

const props = defineProps({
    show: Boolean,
    quotedPost: Object,
    forumId: Number,
});

const emit = defineEmits(["close"]);

const newPostContent = ref("");
const newPostTitle = ref("");

const img = ref(null);
const imgPreview = ref(null);
const isModalOpen = ref(false);
const fileInput = ref(null);

const localErrorMessage = ref(null);

const onImageChange = (event) => {
    handleImageChange(event, img, imgPreview, localErrorMessage);
};

// ファイル選択ボタンをクリックしたときの処理
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する
const removeImage = () => {
    img.value = null;
    imgPreview.value = null;
    localErrorMessage.value = null;
};

// キャンセルボタン
const cancel = () => {
    emit("close");
};

// 引用付き投稿を送信
const submitQuotePost = () => {
    router.post(route("forum.store"), {
        title: newPostTitle.value || null, // 投稿のタイトルを追加
        message: newPostContent.value,
        forum_id: props.forumId, // Forum IDを追加
        quoted_post_id: props.quotedPost.id,
    });

    console.log("props.quotedPost.id:", props.quotedPost.id);
    console.log("props.forumId:", props.forumId);
    console.log("newPostTitle.value:", newPostTitle.value);
    console.log("newPostContent.value:", newPostContent.value);

    if (img.value) {
        formData.append("img", img.value);
    }

    emit("close"); // フォームを閉じる
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    >
        <div class="bg-white p-4 rounded-lg shadow-md max-w-md w-full">
            <!-- 引用元の投稿表示 -->
            <div class="border p-2 rounded-lg mb-4 bg-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">
                    引用元の投稿
                </h3>
                <p class="text-gray-600">{{ quotedPost.message }}</p>
                <!-- メッセージを直接表示 -->
            </div>

            <!-- 新しい投稿の入力フォーム -->
            <div class="relative">
                <textarea
                    v-model="newPostContent"
                    class="w-full border rounded p-2 mb-4"
                    placeholder="投稿文を入力してください"
                    rows="4"
                ></textarea>
                <!-- ファイル選択アイコン -->
                <div
                    class="absolute right-2 bottom-8 bg-gray-300 text-black transition hover:bg-gray-400 hover:text-white rounded-md flex items-center justify-center cursor-pointer"
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
            <div v-if="localErrorMessage" class="text-red-500 mt-2">
                {{ localErrorMessage }}
            </div>
            <!-- プレビュー表示 -->
            <div v-if="imgPreview" class="relative mt-2 inline-block">
                <!-- プレビュー画像 -->
                <img
                    :src="imgPreview"
                    alt="画像プレビュー"
                    class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                    @click="isModalOpen = true"
                />
                <!-- プレビュー画像削除ボタン -->
                <div
                    class="absolute top-0 right-0 bg-white rounded-full p-1 cursor-pointer flex items-center justify-center"
                    @click="removeImage"
                    title="画像を削除"
                    style="width: 24px; height: 24px"
                >
                    <i
                        class="bi bi-x-circle text-black hover:text-gray-500"
                    ></i>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end space-x-2">
                <button
                    @click="cancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 text-gray-700 font-medium transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
                <button
                    @click="submitQuotePost"
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>

        <!-- コメント画像モーダル -->
        <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
            <img
                :src="imgPreview"
                alt="投稿画像"
                class="max-w-full max-h-full rounded-lg"
            />
        </ImageModal>
    </div>
</template>

<style>
.link-hover:hover {
    opacity: 70%;
}
</style>
