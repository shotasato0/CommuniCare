<script setup>
import { ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    quotedPost: Object,
});

const emit = defineEmits(["close"]);

const newPostContent = ref("");

// キャンセルボタン
const cancel = () => {
    emit("close");
};

// 引用付き投稿を送信
const submitQuotePost = () => {
    router.post(route("post.store"), {
        message: newPostContent.value,
        quoted_post_id: props.quotedPostId,
    });

    emit("close"); // フォームを閉じる
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50"
    >
        <div class="bg-white p-4 rounded shadow-md max-w-md w-full">
            <!-- 引用元の投稿表示 -->
            <div class="border p-2 rounded mb-4 bg-gray-100">
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
                    class="px-3 py-1 rounded bg-gray-300 text-gray-800 hover:bg-gray-400"
                >
                    キャンセル
                </button>
                <button
                    @click="submitQuotePost"
                    class="px-3 py-1 rounded bg-blue-500 text-white hover:bg-blue-600"
                >
                    投稿する
                </button>
            </div>
        </div>
    </div>
</template>
