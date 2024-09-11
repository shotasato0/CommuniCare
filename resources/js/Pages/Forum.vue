<script setup>
import { ref, watchEffect } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// usePage から props を取得し、posts が存在しない場合のフォールバックを設定
const pageProps = usePage().props.value || {};
console.log("Initial pageProps:", pageProps); // デバッグ用: 初期propsの確認

const posts = ref(pageProps.posts || []); // 初期投稿データが渡されない場合は空の配列
console.log("Initial posts data:", posts.value); // デバッグ用: 初期のpostsの確認

// アプリ名とフォームデータ
const appName = "CommuniCare";
const postData = ref({
    title: "",
    message: "",
});

// 投稿データの送信
const submitPost = () => {
    console.log("Sending post data:", postData.value); // デバッグ用: 送信前の投稿データ確認

    router.post(route("forum.store"), postData.value, {
        onSuccess: () => {
            console.log("Post submitted successfully"); // デバッグ用: 投稿成功時のメッセージ
            postData.value = { title: "", message: "" }; // フォームリセット
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};

// 投稿の削除
const deletePost = (postId) => {
    console.log("Deleting post with ID:", postId); // デバッグ用: 削除対象の投稿ID確認

    router.delete(`/forum/post/${postId}`, {
        onSuccess: () => {
            console.log("Post deleted successfully"); // デバッグ用: 削除成功時のメッセージ
            posts.value = posts.value.filter((post) => post.id !== postId); // 削除後の投稿をリフレッシュ
        },
        onError: (errors) => {
            console.error("削除に失敗しました:", errors);
        },
    });
};

// watchEffect を使ってpostsの更新を監視し、デバッグ情報を表示
watchEffect(() => {
    console.log("Updated posts data:", posts.value); // デバッグ用: postsが更新されるたびに表示
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="w-11/12 max-w-screen-md m-auto">
            <h1 class="text-xl font-bold mt-5">{{ appName }}</h1>

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
                        />
                    </div>
                    <div class="flex flex-col mt-2">
                        <p class="font-bold">本文</p>
                        <textarea
                            v-model="postData.message"
                            class="border rounded px-2"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button
                            class="my-2 px-2 py-1 rounded bg-blue-300 text-blue-900 font-bold link-hover cursor-pointer"
                        >
                            投稿
                        </button>
                    </div>
                </form>
            </div>

            <!-- 投稿一覧 -->
            <div
                v-for="(post, index) in posts"
                :key="index"
                class="bg-white rounded-md mt-1 mb-5 p-3"
            >
                <!-- スレッド -->
                <div>
                    <p class="mb-2 text-xs">
                        {{ post.created_at }} ＠{{ post.user.name }}
                    </p>
                    <p class="mb-2 text-xl font-bold">
                        {{ post.title }}
                    </p>
                    <p class="mb-2">{{ post.message }}</p>
                </div>

                <!-- 削除ボタン -->
                <form
                    class="flex justify-end mt-5"
                    @submit.prevent="deletePost(post.id)"
                >
                    <button
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        削除
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.link-hover:hover {
    opacity: 70%;
}
</style>
