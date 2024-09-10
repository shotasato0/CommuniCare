<template>
    <div class="w-11/12 max-w-screen-md m-auto">
        <h1 class="text-xl font-bold mt-5">{{ appName }}</h1>

        <!-- 投稿フォーム -->
        <div class="bg-white rounded-md mt-5 p-3">
            <form @submit.prevent="submitPost">
                <div class="flex">
                    <label class="font-bold">名前</label>
                    <input
                        v-model="postData.user_name"
                        class="border rounded px-2 ml-2"
                        type="text"
                        required
                    />
                </div>
                <div class="flex mt-2">
                    <label class="font-bold">件名</label>
                    <input
                        v-model="postData.message_title"
                        class="border rounded px-2 ml-2 flex-auto"
                        type="text"
                        required
                    />
                </div>
                <div class="flex flex-col mt-2">
                    <label class="font-bold">本文</label>
                    <textarea
                        v-model="postData.message"
                        class="border rounded px-2"
                        required
                    ></textarea>
                </div>
                <div class="flex justify-end mt-2">
                    <button
                        class="my-2 px-2 py-1 rounded bg-blue-300 text-blue-900 font-bold"
                    >
                        投稿
                    </button>
                </div>
            </form>
        </div>

        <!-- 投稿一覧 -->
        <div
            v-for="post in posts"
            :key="post.id"
            class="bg-white rounded-md mt-1 mb-5 p-3"
        >
            <p class="mb-2 text-xs">
                {{ post.created_at }} ＠{{ post.user_name }}
            </p>
            <p class="mb-2 text-xl font-bold">{{ post.message_title }}</p>
            <p class="mb-2">{{ post.message }}</p>

            <!-- いいねボタン -->
            <button
                @click="likePost(post.id)"
                class="px-2 py-1 bg-green-500 text-white rounded"
            >
                いいね {{ post.likes.length }}
            </button>

            <!-- 削除ボタン -->
            <button
                @click="deletePost(post.id)"
                class="px-2 py-1 ml-2 bg-red-500 text-white rounded"
            >
                削除
            </button>
        </div>

        <!-- ページネーション -->
        <pagination
            :data="pagination"
            @pagination-change-page="fetchPosts"
        ></pagination>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const appName = usePage().props.appName;
const postData = ref({
    user_name: "",
    message_title: "",
    message: "",
});
const posts = ref([]);
const pagination = ref({});

const fetchPosts = async (page = 1) => {
    await router.get(
        "/api/forum?page=" + page,
        {},
        {
            onSuccess: (data) => {
                posts.value = data.props.posts.data;
                pagination.value = data.props.posts;
            },
            onError: (errors) => {
                console.error("Failed to fetch posts:", errors);
            },
        }
    );
};

const submitPost = async () => {
    await router.post("/api/forum/post", postData.value, {
        onSuccess: () => {
            postData.value = { user_name: "", message_title: "", message: "" }; // フォームリセット
            fetchPosts();
        },
        onError: (errors) => {
            console.error("Failed to submit post:", errors);
        },
    });
};

const likePost = async (postId) => {
    await router.post(
        "/api/forum/" + postId + "/like",
        {},
        {
            onSuccess: () => fetchPosts(),
            onError: (errors) => {
                console.error("Failed to like post:", errors);
            },
        }
    );
};

const deletePost = async (postId) => {
    await router.delete("/api/forum/" + postId, {
        onSuccess: () => fetchPosts(),
        onError: (errors) => {
            console.error("Failed to delete post:", errors);
        },
    });
};

onMounted(() => {
    fetchPosts();
});
</script>
