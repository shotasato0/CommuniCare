<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const postData = ref({
    title: "",
    message: "",
});

// 投稿の送信処理
const submitPost = () => {
    postData.value._token = getCsrfToken(); // CSRFトークンを設定
    router.post(route("forum.store"), postData.value, {
        onSuccess: (response) => {
            const newPost = response.props.newPost; // 新しい投稿を取得
            postData.value = { title: "", message: "" }; // フォームをリセット

            // ページの履歴を更新して、リロード時に誤ったGETリクエストを防ぐ
            router.get(route("forum.index")); // getで履歴を置き換え
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
                <p class="font-bold">件名</p>
                <input
                    v-model="postData.title"
                    class="border rounded px-2 ml-2 flex-auto"
                    type="text"
                    required
                    placeholder="件名を入力してください"
                />
            </div>
            <div class="flex flex-col mt-2">
                <p class="font-bold">本文</p>
                <textarea
                    v-model="postData.message"
                    class="border rounded px-2"
                    required
                    placeholder="本文を入力してください"
                ></textarea>
            </div>
            <div class="flex justify-end mt-2">
                <button
                    class="my-2 px-2 py-1 rounded bg-blue-300 text-blue-900 font-bold link-hover cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
