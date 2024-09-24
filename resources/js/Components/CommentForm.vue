<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

// propsでpostIdとcommentDataを受け取る
const props = defineProps({
    postId: Number,
    parentId: { type: Number, default: null },
    replyToName: { type: String, default: "" },
});

// コメントデータを管理するref
const commentData = ref({
    post_id: props.postId,
    parent_id: props.parentId,
    message: "",
    replyToName: props.replyToName,
});

// コメントの送信処理
const submitComment = () => {
    commentData.value._token = getCsrfToken();
    commentData.value.post_id = props.postId; // 送信対象の投稿IDをセット

    // コメントデータをサーバーに送信
    router.post(
        route("comment.store", { post: props.postId }),
        commentData.value,
        {
            onSuccess: () => {
                // フォームのリセット
                commentData.value = {
                    post_id: null,
                    parent_id: null,
                    message: "",
                };
                router.get(route("forum.index")); // getで履歴を置き換え
            },
            onError: (errors) => {
                console.error("コメントの投稿に失敗しました:", errors);
            },
        }
    );
};
</script>

<template>
    <form @submit.prevent="submitComment">
        <textarea
            v-model="commentData.message"
            class="border rounded mt-4 px-2 w-full"
            required
            :placeholder="
                commentData.replyToName
                    ? `@${commentData.replyToName} にメッセージを送信`
                    : 'メッセージを入力してください'
            "
        ></textarea>
        <div class="flex justify-end mt-2">
            <button
                type="submit"
                class="px-2 py-1 rounded bg-blue-500 text-white font-bold link-hover cursor-pointer"
            >
                <i class="bi bi-send"></i>
            </button>
        </div>
    </form>
</template>
