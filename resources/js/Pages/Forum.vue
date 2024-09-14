<script setup>
import { ref } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs"; // dayjsをインポート

// propsからページのデータを取得
const pageProps = usePage().props;

// postsのデータをpropsから取得し、リアクティブに保持
const posts = ref(pageProps.posts || []);
console.log("Initial posts data:", posts.value); // デバッグ用: 初期のpostsデータを確認

// ログインしているユーザー情報
const auth = pageProps.auth;

// アプリ名とフォームデータ
const appName = "CommuniCare";
const postData = ref({
    title: "",
    message: "",
});

const formatDate = (date) => {
    return dayjs(date).format("YYYY-MM-DD HH:mm:ss");
};

// 投稿データの送信処理
const submitPost = () => {
    console.log("Sending post data:", postData.value); // デバッグ用: 送信前の投稿データ確認

    router.post(route("forum.store"), postData.value, {
        onSuccess: (response) => {
            console.log("Post submitted successfully", response); // デバッグ用: 投稿成功時のメッセージ

            // サーバーから返された新しい投稿データを追加
            const newPost = response.props.newPost; // サーバーから返された正しい投稿IDを使用
            console.log("New post data:", newPost); // デバッグ用: 新しい投稿データ確認
            posts.value = [newPost, ...posts.value]; // 新しい投稿をリストの先頭に追加
            postData.value = { title: "", message: "" }; // フォームのリセット
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors); // 投稿失敗時のエラーメッセージ
        },
    });
};

// 投稿の削除処理
const deletePost = (postId) => {
    console.log("Deleting post with ID:", postId); // デバッグ用: 削除対象の投稿ID確認

    if (confirm("本当に削除しますか？")) {
        router.delete(route("forum.destroy", postId), {
            onSuccess: () => {
                console.log("Post deleted successfully"); // デバッグ用: 削除成功時のメッセージ
                posts.value = posts.value.filter((post) => post.id !== postId); // リストから削除
            },
            onError: (errors) => {
                console.error("削除に失敗しました:", errors); // デバッグ用: 削除失敗時のエラーメッセージ
            },
        });
    }
};
</script>

<template>
    <Head :title="$t('Forum')" />

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
                :key="post.id"
                class="bg-white rounded-md mt-1 mb-5 p-3"
            >
                <!-- スレッド -->
                <div>
                    <p class="mb-2 text-xs">
                        {{ formatDate(post.created_at) }}
                        <span v-if="post.user">＠{{ post.user.name }}</span>
                        <span v-else>＠Unknown</span>
                    </p>
                    <p class="mb-2 text-xl font-bold">{{ post.title }}</p>
                    <p class="mb-2">{{ post.message }}</p>
                </div>

                <!-- ログインユーザーが投稿者の場合のみ削除ボタンを表示 -->
                <div
                    v-if="post.user && post.user.id === auth.user.id"
                    class="flex justify-end mt-5"
                >
                    <button
                        @click.prevent="deletePost(post.id)"
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        削除
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.link-hover:hover {
    opacity: 70%;
}
</style>
