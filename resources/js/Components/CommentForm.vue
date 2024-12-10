<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    postId: Number, // 親投稿のID
    parentId: { type: Number, default: null }, // 親コメントのID
    replyToName: { type: String, default: "" }, // 返信先のユーザー名
    selectedForumId: Number, // 選択されたフォーラムのID
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
    commentData.value.post_id = props.postId;

    router.post(
        route("comment.store", { post: props.postId }),
        commentData.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                // フォームのリセット
                commentData.value = {
                    post_id: props.postId,
                    parent_id: props.parentId,
                    message: "",
                };

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
            },
        }
    );
};
</script>

<template>
    <form @submit.prevent="submitComment" class="relative">
        <!-- メッセージ入力エリアと送信ボタンを横並びに -->
        <div class="flex items-start gap-2">
            <textarea
                v-model="commentData.message"
                class="flex-grow border-gray-300 rounded-md px-2 py-1 text-sm min-h-[2.5rem] max-h-32"
                required
                :placeholder="
                    commentData.replyToName
                        ? `@${commentData.replyToName} にメッセージを送信`
                        : 'メッセージを入力してください'
                "
            ></textarea>

            <button
                type="submit"
                class="px-3 py-1 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer h-[2.5rem]"
            >
                <i class="bi bi-send"></i>
            </button>
        </div>
    </form>
</template>
