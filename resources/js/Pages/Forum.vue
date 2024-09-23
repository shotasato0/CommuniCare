<script setup>
import { ref } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import PostForm from "@/Components/PostForm.vue";
import CommentForm from "@/Components/CommentForm.vue";
import { getCsrfToken } from "@/Utils/csrf";

// propsからページのデータを取得
const pageProps = usePage().props;
const posts = ref(pageProps.posts || []); // 投稿のデータ
const auth = pageProps.auth; // ログインユーザー情報

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// 投稿がクリックされたときにコメントフォームを表示する
const toggleCommentForm = (postId, parentId = null, replyToName = "") => {
    commentFormVisibility.value[postId] = !commentFormVisibility.value[postId];
};

const appName = "CommuniCare"; // アプリ名

const formatDate = (date) => dayjs(date).format("YYYY-MM-DD HH:mm:ss");

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

// コメントの削除処理
const deleteComment = (postId, commentId) => {
    if (confirm("本当にコメントを削除しますか？")) {
        router.delete(route("comment.destroy", commentId), {
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(), // CSRFトークンを設定
            },
            onSuccess: () => {
                const postIndex = posts.value.findIndex(
                    (post) => post.id === postId
                );
                if (postIndex !== -1) {
                    posts.value[postIndex].comments = posts.value[
                        postIndex
                    ].comments.filter((comment) => comment.id !== commentId);
                }
            },
            onError: (errors) => {
                console.error("コメントの削除に失敗しました:", errors);
            },
        });
    }
};

// ユーザーがコメントの作成者かどうかを確認
const isCommentAuthor = (comment) => {
    return auth.user && comment.user && auth.user.id === comment.user.id;
};
</script>

<template>
    <Head :title="$t('Forum')" />

    <AuthenticatedLayout>
        <div class="w-11/12 max-w-screen-md m-auto">
            <h1 class="text-xl font-bold mt-5">{{ appName }}</h1>

            <PostForm />

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
                            @click="
                                toggleCommentForm(
                                    post.id,
                                    comment.id,
                                    comment.user ? comment.user.name : 'Unknown'
                                )
                            "
                            class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                        >
                            <i class="bi bi-reply"></i>
                        </button>

                        <!-- コメント削除ボタン -->
                        <button
                            v-if="isCommentAuthor(comment)"
                            @click="deleteComment(post.id, comment.id)"
                            class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- 返信と削除ボタン -->
                <div class="flex justify-end mt-2 space-x-2">
                    <!-- 投稿に対する返信ボタン -->
                    <button
                        @click="
                            toggleCommentForm(
                                post.id,
                                null,
                                post.user ? post.user.name : 'Unknown'
                            )
                        "
                        class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                    >
                        <i class="bi bi-reply"></i>
                    </button>
                    <!-- 投稿の削除ボタン -->
                    <button
                        v-if="post.user && post.user.id === auth.user.id"
                        @click.prevent="deletePost(post.id)"
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <!-- コメントフォーム -->
                <CommentForm
                    v-if="commentFormVisibility[post.id]"
                    :postId="post.id"
                    :parentId="null"
                    :replyToName="post.user ? post.user.name : 'Unknown'"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.link-hover:hover {
    opacity: 70%;
}
</style>
