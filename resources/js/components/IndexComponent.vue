<template>
    <div class="w-11/12 max-w-screen-md m-auto">
        <!-- タイトル -->
        <h1 class="text-xl font-bold mt-5">{{ appName }}</h1>

        <!-- 入力フォーム -->
        <div class="bg-white rounded-md mt-5 p-3">
            <form @submit.prevent="submitPost">
                <div class="flex mt-2">
                    <p class="font-bold">件名</p>
                    <input
                        class="border rounded px-2 ml-2 flex-auto"
                        type="text"
                        v-model="newPost.title"
                        required
                    />
                </div>
                <div class="flex flex-col mt-2">
                    <p class="font-bold">本文</p>
                    <textarea
                        class="border rounded px-2"
                        v-model="newPost.message"
                        required
                    ></textarea>
                </div>
                <div class="flex justify-end mt-2">
                    <input
                        class="my-2 px-2 py-1 rounded bg-blue-300 text-blue-900 font-bold link-hover cursor-pointer"
                        type="submit"
                        value="投稿"
                    />
                </div>
            </form>
        </div>

        <!-- 検索フォーム -->
        <div class="bg-white rounded-md mt-3 p-3">
            <form @submit.prevent="searchPosts">
                <div class="mx-1 flex">
                    <input
                        class="border rounded px-2 flex-auto"
                        type="text"
                        v-model="searchQuery"
                        required
                    />
                    <input
                        class="ml-2 px-2 py-1 rounded bg-gray-500 text-white font-bold link-hover cursor-pointer"
                        type="submit"
                        value="検索"
                    />
                </div>
            </form>
        </div>

        <!-- ページネーション -->
        <pagination :data="posts" @update="updatePosts" />

        <!-- 投稿 -->
        <div
            v-for="post in posts.data"
            :key="post.id"
            class="bg-white rounded-md mt-1 mb-5 p-3"
        >
            <div>
                <p class="mb-2 text-xs">
                    {{ post.created_at }} @{{ post.user.name }}
                </p>
                <p class="mb-2 text-xl font-bold">{{ post.title }}</p>
                <p class="mb-2">{{ post.message }}</p>
            </div>
            <div class="flex mt-5 items-center">
                <!-- 返信フォーム -->
                <reply-form :post-id="post.id" />
                <!-- 削除ボタン -->
                <button
                    class="h-10 px-4 ml-2 rounded bg-red-500 text-white font-bold cursor-pointer"
                    @click="deletePost(post)"
                >
                    削除
                </button>
            </div>

            <hr class="mt-2 m-auto" />
            <!-- 返信表示 -->
            <div class="flex justify-end">
                <div class="w-11/12">
                    <div v-for="comment in post.comments" :key="comment.id">
                        <p class="mt-2 text-xs">
                            {{ comment.created_at }} @{{ comment.user.name }}
                        </p>
                        <p class="my-2 text-sm">{{ comment.message }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ページネーション -->
        <pagination :data="posts" @update="updatePosts" />
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            appName: import.meta.env.VUE_APP_NAME,
            newPost: {
                title: "",
                message: "",
            },
            searchQuery: "",
            posts: {},
        };
    },
    methods: {
        submitPost() {
            // ここで新規投稿を処理するAPI呼び出しを行う
            axios
                .post("/api/posts", this.newPost)
                .then((response) => {
                    // 成功時の処理
                    console.log(response.data);
                })
                .catch((error) => {
                    // エラー処理
                    console.error(error);
                });
        },
        searchPosts() {
            // ここで検索を処理するAPI呼び出しを行う
            axios
                .post("/api/posts/search", { query: this.searchQuery })
                .then((response) => {
                    // 検索結果を処理
                    this.posts = response.data;
                })
                .catch((error) => {
                    // エラー処理
                    console.error(error);
                });
        },
        deletePost(post) {
            if (confirm("この投稿を削除しますか？")) {
                axios
                    .delete(`/api/posts/${post.id}`)
                    .then((response) => {
                        // 削除後の処理
                        this.removePostFromList(post.id);
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            }
        },
        // 投稿リストから削除するヘルパーメソッド
        removePostFromList(postId) {
            this.posts = this.posts.filter((post) => post.id !== postId);
        },
        updatePosts(newData) {
            this.posts = newData;
        },
    },
};
</script>

<style scoped>
/* 必要に応じてスタイルを追加 */
</style>
