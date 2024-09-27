<script setup>
import { ref, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

// propsでpostIdとcommentDataを受け取る
const props = defineProps({
    postId: { type: Number, required: true },
    parentId: { type: Number, default: null },
    replyToName: { type: String, default: "" },
    users: { type: Array, default: () => [] }, // デフォルト値として空配列を指定
});

console.log("props.users:", props.users);

// コメントデータを管理するref
const commentData = ref({
    post_id: props.postId,
    parent_id: props.parentId,
    message: "",
    replyToName: props.replyToName,
});

// メンション候補リストを更新する
const mentionList = reactive([]); // 空配列で初期化

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

// メンションが入力されたかをチェックする関数
const checkMention = () => {
    if (!commentData.value || !commentData.value.message) {
        mentionList.splice(0, mentionList.length);
        return;
    }

    const message = commentData.value.message;
    const lastAtIndex = message.lastIndexOf("@");
    console.log("Current message:", message);
    console.log("Last @ Index:", lastAtIndex);

    if (lastAtIndex !== -1 && lastAtIndex === message.length - 1) {
        mentionList.splice(0, mentionList.length, ...props.users);
    } else if (lastAtIndex !== -1) {
        const query = message.slice(lastAtIndex + 1).toLowerCase();
        const filteredUsers = (props.users || []).filter((user) =>
            user.name.toLowerCase().startsWith(query)
        );
        mentionList.splice(0, mentionList.length, ...filteredUsers);
    } else {
        mentionList.splice(0, mentionList.length);
    }

    console.log("mentionList type:", typeof mentionList);
    console.log("mentionList is array:", Array.isArray(mentionList));
    console.log(
        "mentionList after update:",
        JSON.parse(JSON.stringify(mentionList))
    );
    console.log("mentionList length:", mentionList.length);
};

// メンション候補を選択する関数
const selectMention = (user) => {
    commentData.value.message = commentData.value.message.replace(
        /@\w*$/,
        `@${user.name} `
    );
    mentionList.value = [];
};
</script>

<template>
    <form @submit.prevent="submitComment" class="relative">
        <!-- メッセージ入力 -->
        <textarea
            v-model="commentData.message"
            @input="checkMention"
            class="border rounded mt-4 px-2 py-1 w-full"
            required
            :placeholder="
                commentData.replyToName
                    ? `@${commentData.replyToName} にメッセージを送信`
                    : 'メッセージを入力してください'
            "
        ></textarea>

        <!-- メンション候補リストの表示 -->
        <div
            v-if="mentionList.length > 0"
            class="absolute z-10 bg-white border border-gray-300 mt-2 max-h-40 overflow-y-auto w-full"
        >
            <ul class="list-none p-0 m-0">
                <li
                    v-for="user in mentionList"
                    :key="user.id"
                    @click="selectMention(user)"
                    class="cursor-pointer px-4 py-2 hover:bg-gray-200"
                >
                    @{{ user.name }}
                </li>
            </ul>
        </div>

        <!-- 送信ボタン -->
        <div class="flex justify-end mt-2">
            <button
                type="submit"
                class="px-2 py-1 bg-blue-500 text-white rounded font-bold hover:bg-blue-600"
            >
                <i class="bi bi-send"></i>
            </button>
        </div>
    </form>
</template>
