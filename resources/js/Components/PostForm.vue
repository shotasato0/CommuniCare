<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    forumId: [String, Number], // String または Number 型を許容
});

const postData = ref({
    title: "",
    message: "",
    forum_id: props.forumId ? Number(props.forumId) : null, // forum_id を追加し、初期値を適切に設定
});

// forumIdの変更を監視し、postDataに反映
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// 投稿の送信処理
const submitPost = () => {
    if (!postData.value.forum_id || postData.value.forum_id === 0) {
        console.error("有効な掲示板IDが選択されていません。");
        return;
    }

    postData.value._token = getCsrfToken(); // CSRFトークンを設定
    router.post(route("forum.store"), postData.value, {
        onSuccess: () => {
            postData.value = {
                title: "",
                message: "",
                forum_id: props.forumId ? Number(props.forumId) : null,
            }; // フォームをリセット
            router.get(
                route("forum.index", { forum_id: postData.value.forum_id }),
                {
                    preserveState: true,
                }
            ); // 選択されたフォーラムにリダイレクト
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};
</script>

<template>
    <!-- 投稿フォーム -->
    <div class="bg-white rounded-md mt-5 p-3">
        <form @submit.prevent="submitPost">
            <div class="flex mt-2">
                <p class="font-medium">件名</p>
                <input
                    v-model="postData.title"
                    class="border-gray-300 rounded-md px-2 ml-2 flex-auto"
                    type="text"
                    required
                    placeholder="件名を入力してください"
                />
            </div>
            <div class="flex flex-col mt-2">
                <p class="font-medium">本文</p>
                <textarea
                    v-model="postData.message"
                    class="border-gray-300 rounded-md px-2"
                    required
                    placeholder="本文を入力してください"
                ></textarea>
            </div>
            <div class="flex justify-end mt-2">
                <button
    class="my-2 px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer"
>
    <i class="bi bi-send"></i>
</button>

            </div>
        </form>
    </div>
</template>
