<script setup>
import { ref } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import PostForm from "@/Components/PostForm.vue";
import CommentForm from "@/Components/CommentForm.vue";
import CommentList from "@/Components/CommentList.vue";
import { getCsrfToken } from "@/Utils/csrf";

// propsからページのデータを取得
const pageProps = usePage().props;
const posts = ref(pageProps.posts || []); // 投稿のデータ
const auth = pageProps.auth; // ログインユーザー情報

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// コメントフォームの表示・非表示を切り替える関数
const toggleCommentForm = (postId, parentId = null, replyToName = "") => {
    // 既存のフォームの状態があるかチェック
    const currentVisibility = commentFormVisibility.value[postId] || {};
    // フォームの表示・非表示を切り替えつつ、parentIdとreplyToNameを保持する
    commentFormVisibility.value[postId] = {
        isVisible: !currentVisibility.isVisible, // 表示状態を反転
        parentId: parentId, // 返信元のコメントID
        replyToName: replyToName, // 返信相手の名前
    };
};

const appName = "CommuniCare"; // アプリ名

const formatDate = (date) => dayjs(date).format("YYYY-MM-DD HH:mm:ss");

const deleteItem = (type, id) => {
    const confirmMessage =
        type === "post"
            ? "本当に投稿を削除しますか？"
            : "本当にコメントを削除しますか？";

    // ユーザーが確認した場合のみ削除
    if (confirm(confirmMessage)) {
        // 削除対象が投稿かコメントかでルートを変更
        const routeName = type === "post" ? "forum.destroy" : "comment.destroy";
        // Inertiaのdeleteメソッドを使用して削除
        router.delete(route(routeName, id), {
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            // 削除成功時の処理
            onSuccess: () => {
                // 削除対象が投稿の場合
                if (type === "post") {
                    posts.value = posts.value.filter((post) => post.id !== id);
                } else {
                    // 削除対象がコメントの場合
                    const postIndex = posts.value.findIndex((post) =>
                        post.comments.some((comment) => comment.id === id)
                    );
                    if (postIndex !== -1) {
                        // 削除対象のコメントを削除
                        posts.value[postIndex].comments = posts.value[
                            postIndex
                        ].comments.filter((comment) => comment.id !== id);
                    }
                }
            },
            // 削除失敗時の処理
            onError: (errors) => {
                console.error("削除に失敗しました:", errors);
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
                v-for="post in posts"
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

                <h3 class="font-bold mb-2">コメント一覧</h3>
                <CommentList
                    :comments="post.comments"
                    :postId="post.id"
                    :formatDate="formatDate"
                    :isCommentAuthor="isCommentAuthor"
                    :deleteItem="deleteItem"
                    :toggleCommentForm="toggleCommentForm"
                />

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
                        @click.prevent="deleteItem('post', post.id)"
                        class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <!-- コメントフォーム -->
                <CommentForm
                    v-if="commentFormVisibility[post.id]?.isVisible"
                    :postId="post.id"
                    :parentId="commentFormVisibility[post.id]?.parentId"
                    :replyToName="commentFormVisibility[post.id]?.replyToName"
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
