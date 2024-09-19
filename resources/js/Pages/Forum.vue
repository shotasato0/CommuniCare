<script setup>
import { ref, onMounted } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";

// propsからページのデータを取得
const pageProps = usePage().props;
const posts = ref(pageProps.posts || []); // 投稿のデータ
const auth = pageProps.auth; // ログインユーザー情報

// CSRFトークンを取得する関数
const getCsrfToken = () =>
    document.querySelector('meta[name="csrf-token"]').getAttribute("content");

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// 投稿がクリックされたときにコメントフォームを表示する
const toggleCommentForm = (postId, parentId = null) => {
    commentFormVisibility.value[postId] = !commentFormVisibility.value[postId];
    commentData.value.post_id = postId;
    commentData.value.parent_id = parentId;
};

const appName = "CommuniCare"; // アプリ名
const postData = ref({
    title: "",
    message: "",
});

// ユーザーがコメントを送信する際にバックエンドに送信されるデータを格納
const commentData = ref({
    post_id: null,
    parent_id: null, // 初期値はnull、通常のコメントの場合はそのまま
    message: "",
});

const formatDate = (date) => dayjs(date).format("YYYY-MM-DD HH:mm:ss");

// 投稿の送信処理
const submitPost = () => {
    postData.value._token = getCsrfToken(); // CSRFトークンを設定
    router.post(route("forum.store"), postData.value, {
        onSuccess: (response) => {
            const newPost = response.props.newPost; // 新しい投稿を取得
            posts.value.unshift(newPost); // 投稿をリストの先頭に追加
            postData.value = { title: "", message: "" }; // フォームをリセット

            // ページの履歴を更新して、リロード時に誤ったGETリクエストを防ぐ
            router.replace(route("forum.index")); // replaceで履歴を置き換え
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};

const submitComment = () => {
    // CSRFトークンを設定
    commentData.value._token = getCsrfToken();

    // コメントデータをサーバーに送信
    router.post(route("comment.store"), commentData.value, {
        onSuccess: (response) => {
            const newComment = response.props.newComment;

            // 新しいコメントを投稿に追加
            const postIndex = posts.value.findIndex(
                (post) => post.id === newComment.post_id
            );
            if (postIndex !== -1) {
                if (!posts.value[postIndex].comments) {
                    posts.value[postIndex].comments = [];
                }
                posts.value[postIndex].comments.push(newComment);
            }

            // フォームのリセット
            commentData.value = { post_id: null, parent_id: null, message: "" };

            // ページの履歴を更新して、リロード時に誤ったGETリクエストを防ぐ
            router.replace(route("forum.index")); // replaceで履歴を置き換え
        },
        onError: (errors) => {
            console.error("コメントの投稿に失敗しました:", errors);
        },
    });
};

// 投稿の削除処理
const deletePost = (postId) => {
    if (confirm("本当に削除しますか？")) {
        router.delete(route("forum.destroy", postId), {
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(), // CSRFトークンを設定
            },
            onSuccess: () => {
                posts.value = posts.value.filter((post) => post.id !== postId); // 投稿を削除
            },
            onError: (errors) => {
                console.error("削除に失敗しました:", errors);
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

                <!-- コメント一覧 -->
                <div
                    v-if="post.comments && post.comments.length > 0"
                    class="mt-4"
                >
                    <h3 class="font-bold mb-2">コメント</h3>
                    <div
                        v-for="comment in post.comments"
                        :key="comment.id"
                        class="ml-4 mb-2"
                    >
                        <p class="text-xs">
                            {{ formatDate(comment.created_at) }}
                            <span v-if="comment.user"
                                >＠{{ comment.user.name }}</span
                            >
                            <span v-else>＠Unknown</span>
                        </p>
                        <p>{{ comment.message }}</p>

                        <!-- 返信ボタン -->
                        <button
                            @click="toggleCommentForm(post.id, comment.id)"
                            class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                        >
                            返信
                        </button>
                    </div>
                </div>

                <!-- 返信と削除ボタン -->
                <div class="flex justify-end mt-2 space-x-2">
                    <button
                        @click="toggleCommentForm(post.id)"
                        class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                    >
                        返信
                    </button>
                    <button
                        v-if="post.user && post.user.id === auth.user.id"
                        @click.prevent="deletePost(post.id)"
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        削除
                    </button>
                </div>

                <!-- コメントフォーム -->
                <div v-if="commentFormVisibility[post.id]" class="mt-4">
                    <form @submit.prevent="submitComment">
                        <textarea
                            v-model="commentData.message"
                            class="border rounded px-2 w-full"
                            required
                            placeholder="コメントを入力してください"
                        ></textarea>
                        <div class="flex justify-end mt-2">
                            <button
                                type="submit"
                                class="px-2 py-1 rounded bg-blue-500 text-white font-bold link-hover cursor-pointer"
                            >
                                送信
                            </button>
                        </div>
                    </form>
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
