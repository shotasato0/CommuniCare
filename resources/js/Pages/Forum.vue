<script setup>
import { ref } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import dayjs from "dayjs";
import PostForm from "@/Components/PostForm.vue";
import CommentForm from "@/Components/CommentForm.vue";
import ParentComment from "@/Components/ParentComment.vue"; // 新しいコンポーネント
import Pagination from "@/Components/Pagination.vue";
import { getCsrfToken } from "@/Utils/csrf";
import Show from "./Users/Show.vue";
import SearchForm from "@/Components/SearchForm.vue";
import List from "./Unit/List.vue";

// propsからページのデータを取得
const pageProps = usePage().props;
const posts = ref(pageProps.posts || []); // 投稿のデータ
const auth = pageProps.auth; // ログインユーザー情報
const units = pageProps.units; // 部署のデータ
const selectedPost = ref(null); // 選択された投稿
const isUserProfileVisible = ref(false); // ユーザーの詳細ページの表示状態
const sidebarVisible = ref(false); // サイドバーの表示状態

const openUserProfile = (post) => {
    selectedPost.value = post;
    isUserProfileVisible.value = true; // ユーザーの詳細ページを表示
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

// 投稿を選択する関数
const selectPost = (post) => {
    selectedPost.value = post;
};

const toggleSidebar = () => {
    sidebarVisible.value = !sidebarVisible.value;
    console.log("sidebarVisible.value:", sidebarVisible.value);
};

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// コメントフォームの表示・非表示を切り替える関数
const toggleCommentForm = (postId, parentId = "post", replyToName = "") => {
    // postIdでコメントフォームの状態が初期化されているか確認
    if (!commentFormVisibility.value[postId]) {
        commentFormVisibility.value[postId] = {};
    }

    // コメントフォームがparentIdで初期化されているか確認
    if (!commentFormVisibility.value[postId][parentId]) {
        commentFormVisibility.value[postId][parentId] = {
            isVisible: false,
            replyToName: "",
        };
    }

    // コメントフォームの表示・非表示を切り替え
    commentFormVisibility.value[postId][parentId].isVisible =
        !commentFormVisibility.value[postId][parentId].isVisible;
    commentFormVisibility.value[postId][parentId].replyToName = replyToName;
};

const appName = "CommuniCare"; // アプリ名

const formatDate = (date) => dayjs(date).format("YYYY-MM-DD HH:mm:ss");

// 再帰的にコメントを検索する関数
const findCommentRecursive = (comments, commentId) => {
    for (let i = 0; i < comments.length; i++) {
        if (comments[i].id === commentId) {
            return comments[i]; // 削除対象のコメントを見つけた場合に返す
        }
        if (comments[i].children && comments[i].children.length > 0) {
            const foundComment = findCommentRecursive(
                comments[i].children,
                commentId
            );
            if (foundComment) {
                return foundComment;
            }
        }
    }
    return null;
};

const deleteItem = (type, id) => {
    const confirmMessage =
        type === "post"
            ? "本当に投稿を削除しますか？"
            : "本当にコメントを削除しますか？";

    // ユーザーが確認した場合のみ削除
    if (confirm(confirmMessage)) {
        const routeName = type === "post" ? "forum.destroy" : "comment.destroy";
        router.delete(route(routeName, id), {
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            onSuccess: () => {
                if (type === "post") {
                    // 投稿を削除したら、postIdで該当の投稿をフィルタリングして削除
                    posts.value.data = posts.value.data.filter(
                        (post) => post.id !== id
                    );
                } else {
                    // コメントの削除処理
                    const postIndex = posts.value.data.findIndex((post) =>
                        findCommentRecursive(post.comments, id)
                    );

                    console.log("postIndex:", postIndex); // postIndexが-1かどうかを確認

                    if (postIndex !== -1) {
                        let comments = posts.value.data[postIndex].comments;

                        // 再帰的にコメントを削除
                        const deleteCommentRecursive = (comments, id) => {
                            for (let i = 0; i < comments.length; i++) {
                                if (comments[i].id === id) {
                                    comments.splice(i, 1); // 削除対象のコメントを配列から削除
                                    return;
                                }
                                if (
                                    comments[i].children &&
                                    comments[i].children.length > 0
                                ) {
                                    deleteCommentRecursive(
                                        comments[i].children,
                                        id
                                    ); // 子コメントがあれば再帰的に削除
                                }
                            }
                        };

                        deleteCommentRecursive(comments, id); // 削除処理の実行

                        // 手動でVueに変更を知らせる
                        posts.value.data[postIndex].comments = [...comments];

                        // 削除後のコメントデータを確認
                        console.log(
                            "削除後のコメントデータ:",
                            posts.value.data[postIndex].comments
                        );
                    } else {
                        console.error(
                            "削除対象のコメントが見つかりませんでした。"
                        );
                    }
                }
            },
            onError: (errors) => {
                console.error("削除に失敗しました:", errors);
            },
        });
    }
};

// 再帰的にすべての子コメントを含めてコメント数を取得する関数
const getCommentCountRecursive = (comments) => {
    let count = comments.length;

    comments.forEach((comment) => {
        if (comment.children && comment.children.length > 0) {
            count += getCommentCountRecursive(comment.children); // 再帰的に子コメントをカウント
        }
    });

    return count;
};

// 現在のコメント数を取得する
const getCurrentCommentCount = (post) => {
    return getCommentCountRecursive(post.comments);
};

// ユーザーがコメントの作成者かどうかを確認
const isCommentAuthor = (comment) => {
    return auth.user && comment.user && auth.user.id === comment.user.id;
};

// 検索結果の表示状態
const search = ref(pageProps.search || "");
</script>

<template>
    <Head :title="$t('Forum')" />

    <AuthenticatedLayout>
        <div class="flex">
            <!-- サイドバー -->
            <List
                :units="units"
                class="sidebar-mobile  p-4 mt-16 lg:block"
                :class="{ visible: sidebarVisible }"
                ref="sidebar"
            />

            <!-- メインコンテンツエリア -->
            <div class="flex-1 max-w-4xl mx-auto p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1
                        class="text-xl font-bold cursor-pointer"
                        @click="toggleSidebar"
                    >
                        {{ appName }}
                    </h1>

                    <!-- 検索フォーム -->
                    <SearchForm class="ml-auto" />
                </div>

                <!-- 検索結果 -->
                <div v-if="search" class="text-lg font-bold mb-4">
                    <p>検索結果: {{ posts.total }}件</p>
                </div>

                <!-- 上部ページネーション -->
                <Pagination :links="posts.links" class="mb-4" />

                <!-- 投稿フォーム -->
                <PostForm class="mb-6" />

                <!-- 投稿一覧 -->
                <div
                    v-for="post in posts.data"
                    :key="post.id"
                    class="bg-white rounded-md shadow-md mb-6 p-4"
                >
                    <div>
                        <p class="mb-2 text-xs text-gray-500">
                            {{ formatDate(post.created_at) }}
                            <span
                                v-if="post.user"
                                class="flex items-center space-x-2"
                            >
                                <!-- ユーザーアイコンの表示 -->
                                <img
                                    v-if="post.user.icon"
                                    :src="
                                        post.user.icon.startsWith('/storage/')
                                            ? post.user.icon
                                            : `/storage/${post.user.icon}`
                                    "
                                    alt="User Icon"
                                    class="w-6 h-6 rounded-full cursor-pointer"
                                    @click="openUserProfile(post)"
                                />
                                <img
                                    v-else
                                    src="https://via.placeholder.com/40"
                                    alt="Default Icon"
                                    class="w-6 h-6 rounded-full cursor-pointer"
                                    @click="openUserProfile(post)"
                                />

                                <!-- 投稿者名の表示 -->
                                <span
                                    @click="openUserProfile(post)"
                                    class="hover:bg-blue-300 p-1 rounded cursor-pointer"
                                >
                                    ＠{{ post.user.name }}
                                </span>
                            </span>
                            <span v-else>＠Unknown</span>
                        </p>
                        <p class="mb-2 text-xl font-bold">{{ post.title }}</p>
                        <p class="mb-2">{{ post.message }}</p>

                        <!-- ボタンを投稿の下、右端に配置 -->
                        <div class="flex justify-end space-x-2 mt-2">
                            <!-- 投稿に対する返信ボタン -->
                            <button
                                @click="
                                    toggleCommentForm(
                                        post.id,
                                        'post',
                                        post.user ? post.user.name : 'Unknown'
                                    )
                                "
                                class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                            >
                                <i class="bi bi-reply"></i>
                            </button>
                            <!-- 投稿の削除ボタン -->
                            <button
                                v-if="
                                    post.user && post.user.id === auth.user.id
                                "
                                @click.prevent="deleteItem('post', post.id)"
                                class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>

                        <!-- 投稿へのコメントフォーム -->
                        <CommentForm
                            v-if="
                                commentFormVisibility[post.id]?.['post']
                                    ?.isVisible
                            "
                            :postId="post.id"
                            :parentId="null"
                            :replyToName="
                                commentFormVisibility[post.id]?.['post']
                                    ?.replyToName
                            "
                            class="mt-4"
                        />
                    </div>

                    <h3 class="font-bold mt-8 mb-2">
                        {{ getCurrentCommentCount(post) }}件のコメント
                    </h3>

                    <!-- 親コメントビュー -->
                    <ParentComment
                        :comments="post.comments"
                        :postId="post.id"
                        :formatDate="formatDate"
                        :isCommentAuthor="isCommentAuthor"
                        :deleteItem="deleteItem"
                        :toggleCommentForm="toggleCommentForm"
                        :commentFormVisibility="commentFormVisibility"
                        :openUserProfile="openUserProfile"
                    />
                </div>

                <!-- 下部ページネーション -->
                <Pagination :links="posts.links" class="mt-4" />
            </div>

            <!-- 選択された投稿のユーザーの詳細ページを表示 -->
            <div
                v-if="isUserProfileVisible"
                class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
                @click="closeUserProfile"
            >
                <!-- show.vue のコンポーネントにクリックイベントをストップさせる -->
                <div @click.stop>
                    <Show
                        v-if="selectedPost"
                        :user="selectedPost.user"
                        :units="units"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.link-hover:hover {
    opacity: 70%;
}

/* モバイルサイズ用のスタイル（切り替え可能） */
@media (max-width: 1024px) {
    .sidebar-mobile {
        width: 70%; /* モバイル時のサイドバー幅 */
        transform: translateX(-100%); /* デフォルトで非表示 */
        transition: transform 0.3s ease-in-out;
        z-index: 50;
    }
    .sidebar-mobile.visible {
        transform: translateX(0); /* 表示 */
    }
}
</style>
