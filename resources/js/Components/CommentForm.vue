<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    postId: Number, // 親投稿のID
    parentId: { type: Number, default: null }, // 親コメントのID
    replyToName: { type: String, default: "" }, // 返信先のユーザー名
});

// コメントデータを管理するref
const commentData = ref({
    post_id: props.postId,
    parent_id: props.parentId,
    message: "",
    replyToName: props.replyToName,
});

onMounted(() => {
    // コメントに対する返信の場合のみメンションを追加
    if (props.replyToName && props.parentId) {
        commentData.value.message = `@${props.replyToName} `;
    }
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
                    post_id: props.postId,
                    parent_id: props.parentId,
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
        <!-- メッセージ入力 -->
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

        <!-- 送信ボタン -->
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
