<script setup>
import { ref, defineProps, defineEmits, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    postId: {
        type: Number,
        required: true,
    },
    parentId: {
        type: Number,
        default: null,
    },
    selectedForumId: {
        type: Number,
        required: true,
    },
    replyToName: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "返信",
    },
});

const emit = defineEmits(["cancel"]);
const message = ref("");
const placeholder = ref(`@${props.replyToName} さんへの返信を入力`);

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

// キャンセルハンドラーを追加
const handleCancel = () => {
    // フォームをリセット
    commentData.value = {
        post_id: props.postId,
        parent_id: props.parentId,
        message: "",
        replyToName: props.replyToName,
    };
    // 親コンポーネントにキャンセルイベントを発行
    emit("cancel");
};
</script>

<template>
    <div class="mt-4">
        <h3 class="font-bold mb-2">{{ title }}</h3>
        <form @submit.prevent="submitComment">
            <textarea
                v-model="commentData.message"
                class="w-full p-2 border rounded-md"
                :placeholder="placeholder"
                rows="3"
            ></textarea>
            <div class="flex justify-end space-x-2 mt-2">
                <button
                    type="button"
                    @click="handleCancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 text-gray-700 font-medium transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
                <button
                    type="submit"
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
