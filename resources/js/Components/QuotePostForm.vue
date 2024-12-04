<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    quotedPost: Object,
    forumId: Number,
});

const emit = defineEmits(["close"]);

const newPostContent = ref("");
const newPostTitle = ref("");

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
            <textarea
                v-model="newPostContent"
                class="w-full border rounded p-2 mb-4"
                placeholder="ここにコメントを入力してください"
                rows="4"
            ></textarea>

            <!-- 送信ボタン -->
            <div class="flex justify-end space-x-2">
                <button
                    @click="cancel"
                    class="px-3 py-1 rounded bg-gray-300 text-gray-800 link-hover"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
                <button
                    @click="submitQuotePost"
                    class="px-3 py-1 rounded bg-blue-500 text-white link-hover"
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
