<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

// アプリ名とフォームデータ
const appName = "CommuniCare";
const postData = ref({
    user_name: "",
    message_title: "",
    message: "",
});
const posts = ref([]);
const searchQuery = ref("");
const replyMessage = ref("");

// 投稿データの送信
const submitPost = () => {
    console.log("投稿が送信されました:", postData.value);
    // 実際のAPI呼び出しなどをここで行う
    posts.value.push({
        ...postData.value,
        created_at: new Date().toLocaleString(),
        replies: [],
    });
    postData.value = { user_name: "", message_title: "", message: "" };
};

// 検索処理
const searchPosts = () => {
    console.log("検索クエリ:", searchQuery.value);
    // 実際の検索ロジックをここで処理する
};

// 投稿の削除
const deletePost = (postId) => {
    console.log("削除対象の投稿ID:", postId);
    posts.value = posts.value.filter((post) => post.id !== postId);
};
</script>

<style scoped>
.link-hover:hover {
    opacity: 70%;
}
</style>

<template>
    <AuthenticatedLayout>
        <div class="w-11/12 max-w-screen-md m-auto">
            <h1 class="text-xl font-bold mt-5">{{ appName }}</h1>

            <!-- 投稿フォーム -->
            <div class="bg-white rounded-md mt-5 p-3">
                <form @submit.prevent="submitPost">
                    <div class="flex">
                        <p class="font-bold">名前</p>
                        <input
                            v-model="postData.user_name"
                            class="border rounded px-2 ml-2"
                            type="text"
                            required
                        />
                    </div>
                    <div class="flex mt-2">
                        <p class="font-bold">件名</p>
                        <input
                            v-model="postData.message_title"
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

            <!-- 検索フォーム -->
            <div class="bg-white rounded-md mt-3 p-3">
                <form @submit.prevent="searchPosts">
                    <div class="mx-1 flex">
                        <input
                            v-model="searchQuery"
                            class="border rounded px-2 flex-auto"
                            type="text"
                            placeholder="検索"
                        />
                        <button
                            class="ml-2 px-2 py-1 rounded bg-gray-500 text-white font-bold link-hover cursor-pointer"
                        >
                            検索
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
                        {{ post.created_at }} ＠{{ post.user_name }}
                    </p>
                    <p class="mb-2 text-xl font-bold">
                        {{ post.message_title }}
                    </p>
                    <p class="mb-2">{{ post.message }}</p>
                </div>
                <!-- 削除ボタン -->
                <form
                    class="flex justify-end mt-5"
                    @submit.prevent="deletePost(post.id)"
                >
                    <input
                        v-model="replyMessage"
                        class="border rounded px-2 flex-auto"
                        type="text"
                        placeholder="返信メッセージ"
                    />
                    <button
                        class="px-2 py-1 ml-2 rounded bg-green-600 text-white font-bold link-hover cursor-pointer"
                    >
                        返信
                    </button>
                    <button
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        削除
                    </button>
                </form>
                <!-- 返信 -->
                <hr class="mt-2 m-auto" />
                <div class="flex justify-end">
                    <div class="w-11/12">
                        <div
                            v-for="(reply, index) in post.replies"
                            :key="index"
                        >
                            <p class="mt-2 text-xs">
                                {{ reply.created_at }} ＠{{ reply.user_name }}
                            </p>
                            <p class="my-2 text-sm">{{ reply.message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
