<script setup>
import { ref, onMounted, watch } from "vue";
import { usePage, router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PostForm from "@/Components/PostForm.vue";
import CommentForm from "@/Components/CommentForm.vue";
import ParentComment from "@/Components/ParentComment.vue";
import Pagination from "@/Components/Pagination.vue";
import Show from "./Users/Show.vue";
import SearchForm from "@/Components/SearchForm.vue";
import ListForSidebar from "./Unit/ListForSidebar.vue";
import RightSidebar from "./Unit/RightSidebar.vue";
import LikeButton from "@/Components/LikeButton.vue";
import QuotePostForm from "@/Components/QuotePostForm.vue";
import { formatDate } from "@/Utils/dateUtils";
import {
    findCommentRecursive,
    deleteCommentRecursive,
} from "@/Utils/commentUtils";
import { restoreSelectedUnit } from "@/Utils/sessionUtils";
import { initSelectedForumId } from "@/Utils/initUtils";
import { fetchPostsByForumId } from "@/Utils/fetchPosts";
import { deleteItem } from "@/Utils/deleteItem";

// props を構造分解して取得
const {
    posts: initialPosts = { data: [], links: [] }, // 投稿のデータ
    auth, // ログインユーザー情報
    units: initialUnits = [], // 部署のデータ
    users: initialUsers = [], // ユーザーのデータ
    selectedForumId: forumIdFromProps = null, // 選択された掲示板のID
    search: initialSearch = "", // 検索結果の表示状態
} = usePage().props;

// propsからページのデータを取得
const posts = ref(initialPosts); // 投稿のデータ
const units = ref(initialUnits); // 部署のデータ
const selectedPost = ref(null); // 選択された投稿
const isUserProfileVisible = ref(false); // ユーザーの詳細ページの表示状態
const sidebarVisible = ref(false); // サイドバーの表示状態
const users = ref(initialUsers); // ユーザーのデータ
const sidebar = ref(null); // サイドバーのコンポーネントインスタンス
const selectedForumId = ref(forumIdFromProps); // 選択された掲示板のID
const selectedUnitUsers = ref([]); // 選択されたユニットのユーザーリスト
const selectedUnitName = ref(""); // 選択されたユニットの名前
const search = ref(initialSearch); // 検索結果の表示状態
const quotedPost = ref(null); // 引用投稿
const showPostForm = ref(false); // 引用投稿フォームの表示制御
const activeUnitId = ref(null); // 選択中の部署IDを管理

const quotePost = (post) => {
    quotedPost.value = post; // post全体をセットする
    showPostForm.value = true;
};

onMounted(() => {
    // マウント時にselectedForumIdを初期化
    initSelectedForumId(selectedForumId);
    // マウント時に選択されたユニットのユーザーと名前を復元
    restoreSelectedUnit(selectedUnitUsers, selectedUnitName);
    // 保存された部署IDを復元
    const savedUnitId = localStorage.getItem("lastSelectedUnitId");
    if (savedUnitId) {
        activeUnitId.value = parseInt(savedUnitId);
    }
});

// selectedForumIdの変更を監視し、変更があるたびに投稿を再取得
watch(selectedForumId, (newForumId) => {
    fetchPostsByForumId(router, newForumId);
});

// サイドバーのユーザー選択イベントを受け取る関数
const onUserSelected = (user) => {
    console.log("User selected:", user);
    selectedPost.value = { user }; // `selectedPost`に選択したユーザーをセット
    isUserProfileVisible.value = true; // ユーザープロファイルのポップアップを表示
};

// ユニット選択イベント
const onForumSelected = async (unitId) => {
    const unit = units.value.find((u) => u.id === unitId);
    if (!unit || !unit.forum) {
        console.error("対応する掲示板が見つかりませんでした");
        return;
    }

    // users が配列であることを確認しつつフィルタリング
    const filteredUsers = Array.isArray(users.value)
        ? users.value.filter((user) => user.unit_id === unitId)
        : [];

    selectedForumId.value = unit.forum.id;
    selectedUnitName.value = unit.name;
    selectedUnitUsers.value = filteredUsers;

    sessionStorage.setItem("selectedUnitName", selectedUnitName.value);
    sessionStorage.setItem("selectedUnitUsers", JSON.stringify(filteredUsers));
    localStorage.setItem("lastSelectedUnitId", unitId);

    router.get(route("forum.index", { forum_id: selectedForumId.value }), {
        preserveState: false,
    });
};

const onPageChange = (url) => {
    router.get(url, {
        preserveScroll: true,
        preserveState: true,
        only: ["posts"],
    });
};

const openUserProfile = (post) => {
    selectedPost.value = post;
    isUserProfileVisible.value = true; // ユーザーの詳細ページを表示
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

const toggleSidebar = () => {
    sidebarVisible.value = !sidebarVisible.value;
    console.log("sidebarVisible.value:", sidebarVisible.value);
};

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// コメントフォームの表示・非表示を切り替える関数
const toggleCommentForm = (postId, parentId = "post", replyToName = "") => {
    if (!commentFormVisibility.value[postId]) {
        commentFormVisibility.value[postId] = {};
    }

    if (!commentFormVisibility.value[postId][parentId]) {
        commentFormVisibility.value[postId][parentId] = {
            isVisible: false,
            replyToName: "",
        };
    }

    // フォームの表示を反転
    commentFormVisibility.value[postId][parentId].isVisible =
        !commentFormVisibility.value[postId][parentId].isVisible;
    commentFormVisibility.value[postId][parentId].replyToName = replyToName;

    // 強制的に再描画を促すため、オブジェクトを新しく作り直す
    commentFormVisibility.value = { ...commentFormVisibility.value };
};

const onDeleteItem = (type, id) => {
    deleteItem(type, id, async (deletedId) => {
        if (type === "post") {
            // まずローカルでデータを更新
            posts.value.data = posts.value.data.filter(
                (post) => post.id !== deletedId
            );

            // 削除後に正しいURLに遷移（履歴を置き換える）
            const currentUrl = route("forum.index", {
                forum_id: selectedForumId.value,
            });

            // ブラウザの履歴を置き換え
            window.history.replaceState({}, "", currentUrl);

            // Inertiaを使用してデータを更新
            router.reload({
                only: ["posts"],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        } else if (type === "comment") {
            handleCommentDeletion(deletedId);
        }
    });
};

// コメントの削除を処理する関数
const handleCommentDeletion = (commentId) => {
    const postIndex = posts.value.data.findIndex((post) =>
        findCommentRecursive(post.comments, commentId)
    );

    if (postIndex !== -1) {
        const comments = posts.value.data[postIndex].comments;

        const deleted = deleteCommentRecursive(comments, commentId);

        if (deleted) {
            // Vueに変更を通知
            posts.value.data[postIndex].comments = [...comments];
            console.log(`返信削除成功: ${commentId}`);
        } else {
            console.error(`返信削除に失敗しました: ${commentId}`);
        }
    } else {
        console.error(`削除対象の返信が見つかりませんでした: ${commentId}`);
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

const handleForumSelected = (unitId) => {
    activeUnitId.value = unitId; // 選択された部署IDを保存
    localStorage.setItem("lastSelectedUnitId", unitId); // ローカルストレージに保存
    onForumSelected(unitId);
};
</script>

<template>
    <Head :title="$t('Forum')" />

    <AuthenticatedLayout>
        <div class="flex mt-16">
            <!-- オーバーレイ (サイドバー表示時のみ) -->
            <div
                v-if="sidebarVisible"
                class="overlay"
                @click="toggleSidebar"
            ></div>

            <!-- サイドバー -->
            <ListForSidebar
                :units="units"
                :users="users"
                :active-unit-id="activeUnitId"
                class="sidebar-mobile p-4 sm:mt-16 lg:block"
                :class="{ visible: sidebarVisible }"
                ref="sidebar"
                @user-profile-clicked="onUserSelected"
                v-model:sidebarVisible="sidebarVisible"
                @forum-selected="handleForumSelected"
            />

            <!-- メインコンテンツエリア -->
            <div class="flex-1 max-w-4xl mx-auto p-4">
                <!-- サイドバーのトグルボタンと検索フォーム -->
                <div class="flex items-center mb-4 relative">
                    <h1
                        class="text-lg font-bold cursor-pointer toggle-button"
                        @click="toggleSidebar"
                    >
                        {{ $t("Unit List") }}
                    </h1>

                    <!-- 検索フォーム -->
                    <SearchForm
                        :selected-forum-id="selectedForumId"
                        class="ml-auto"
                    />

                    <!-- 検索結果 -->
                    <div
                        class="absolute top-full right-36 text-sm text-gray-600 mt-1 mb-16"
                    >
                        <p v-if="search">検索結果: {{ posts.total }}件</p>
                    </div>
                </div>

                <!-- 上部ページネーション -->
                <div class="mt-12 h-12 flex items-center justify-center">
                    <Pagination
                        v-if="posts?.links?.length"
                        :links="posts.links"
                        @change="onPageChange"
                    />
                </div>

                <!-- 投稿フォーム -->
                <PostForm
                    v-if="selectedForumId"
                    :forum-id="Number(selectedForumId)"
                    class="mb-6"
                    title="投稿"
                />

                <!-- 投稿一覧 -->
                <div
                    v-if="posts.data && posts.data.length > 0"
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
                                    class="w-12 h-12 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300 mb-1"
                                    @click="openUserProfile(post)"
                                />
                                <img
                                    v-else
                                    src="https://via.placeholder.com/40"
                                    alt="Default Icon"
                                    class="w-12 h-12 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300 mb-1"
                                    @click="openUserProfile(post)"
                                />

                                <!-- 投稿者名の表示 -->
                                <span
                                    @click="openUserProfile(post)"
                                    class="text-sm font-semibold text-gray-800 hover:bg-blue-100 p-1 rounded cursor-pointer"
                                >
                                    ＠{{ post.user.name }}
                                </span>
                            </span>
                            <span v-else>＠Unknown</span>
                        </p>
                        <p class="mb-2 text-xl font-bold">{{ post.title }}</p>

                        <!-- 引用投稿がある場合の表示 -->
                        <div>
                            <!-- 削除済みの場合 -->
                            <template v-if="post.quoted_post_deleted === 1">
                                <p
                                    class="text-gray-500 italic mb-2 p-2 border-l-4 border-gray-300 bg-gray-100"
                                >
                                    引用元の投稿は削除されました
                                </p>
                            </template>

                            <!-- 削除されていない場合 -->
                            <template v-else-if="post.quoted_post">
                                <div
                                    class="quoted-post mb-2 p-2 border-l-4 border-gray-300 bg-gray-100"
                                >
                                    <div class="flex items-center space-x-2">
                                        <img
                                            v-if="
                                                post.quoted_post.user &&
                                                post.quoted_post.user.icon
                                            "
                                            :src="
                                                post.quoted_post.user.icon.startsWith(
                                                    '/storage/'
                                                )
                                                    ? post.quoted_post.user.icon
                                                    : `/storage/${post.quoted_post.user.icon}`
                                            "
                                            alt="User Icon"
                                            class="w-8 h-8 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300 mb-1"
                                            @click="
                                                openUserProfile(
                                                    post.quoted_post
                                                )
                                            "
                                        />
                                        <img
                                            v-else
                                            src="https://via.placeholder.com/40"
                                            alt="Default Icon"
                                            class="w-12 h-12 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300 mb-1"
                                            @click="
                                                openUserProfile(
                                                    post.quoted_post
                                                )
                                            "
                                        />
                                        <span
                                            @click="
                                                openUserProfile(
                                                    post.quoted_post
                                                )
                                            "
                                            class="hover:bg-blue-100 p-1 rounded cursor-pointer text-sm"
                                        >
                                            ＠{{
                                                post.quoted_post.user?.name ||
                                                "Unknown"
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-sm mb-2 font-bold">
                                        {{ post.quoted_post.title }}
                                    </p>
                                    <p class="text-sm mb-2 whitespace-pre-wrap">
                                        {{ post.quoted_post.message }}
                                    </p>
                                </div>
                            </template>
                        </div>

                        <p class="mb-2 whitespace-pre-wrap">
                            {{ post.message }}
                        </p>

                        <!-- ボタンを投稿の下、右端に配置 -->
                        <div class="flex justify-end space-x-2 mt-2">
                            <LikeButton
                                :likeable-id="post.id"
                                :likeable-type="'Post'"
                                :initial-like-count="post.like_count"
                                :initial-is-liked="post.is_liked_by_user"
                            />
                            <!-- 投稿に対する返信ボタン -->
                            <button
                                @click="
                                    toggleCommentForm(
                                        post.id,
                                        'post',
                                        post.user ? post.user.name : 'Unknown'
                                    )
                                "
                                class="px-4 py-2 rounded-md bg-green-100 text-green-700 transition hover:bg-green-300 hover:text-white cursor-pointer"
                                title="返信"
                            >
                                <i class="bi bi-reply"></i>
                            </button>
                            <!-- 引用投稿ボタン -->
                            <button
                                type="button"
                                @click="quotePost(post)"
                                class="px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer flex items-center"
                                title="引用投稿"
                            >
                                <i class="bi bi-chat-quote"></i>
                            </button>

                            <!-- 投稿の削除ボタン -->
                            <button
                                v-if="
                                    post.user && post.user.id === auth.user.id
                                "
                                @click.prevent="onDeleteItem('post', post.id)"
                                class="px-4 py-2 ml-2 rounded-md bg-red-100 text-red-700 transition hover:bg-red-300 hover:text-white cursor-pointer"
                                title="投稿の削除"
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
                            :selected-forum-id="Number(selectedForumId)"
                            :replyToName="
                                commentFormVisibility[post.id]?.['post']
                                    ?.replyToName
                            "
                            @cancel="toggleCommentForm(post.id, 'post')"
                            class="mt-4 comment-form"
                            title="返信"
                        />
                    </div>

                    <h3 class="font-bold mt-8 mb-2">
                        {{ getCurrentCommentCount(post) }}件の返信
                    </h3>

                    <!-- 親コメントビュー -->
                    <ParentComment
                        :comments="post.comments"
                        :postId="post.id"
                        :formatDate="formatDate"
                        :isCommentAuthor="isCommentAuthor"
                        :onDeleteItem="onDeleteItem"
                        :toggleCommentForm="toggleCommentForm"
                        :commentFormVisibility="commentFormVisibility"
                        :openUserProfile="openUserProfile"
                        :selectedForumId="Number(selectedForumId)"
                    />
                </div>

                <!-- 投稿がない場合のメッセージ -->
                <div
                    v-else
                    class="text-center text-gray-500 text-lg font-semibold mt-6"
                >
                    投稿がありません。
                </div>

                <!-- 下部ページネーション -->
                <Pagination
                    :links="posts?.links || []"
                    @change="onPageChange"
                    class="mt-4"
                />
            </div>

            <!-- 右サイドバー -->
            <RightSidebar
                :unit-users="selectedUnitUsers"
                :unit-name="selectedUnitName"
                class="p-4 lg:mt-16 sm:block"
                @user-selected="onUserSelected"
            />

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

            <!-- 引用投稿フォームをモーダルで表示 -->
            <QuotePostForm
                v-if="showPostForm && quotedPost"
                :show="showPostForm"
                :quoted-post="quotedPost"
                :forum-id="Number(selectedForumId)"
                @close="showPostForm = false"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* モバイルサイズ用のスタイル（切り替え可能） */
@media (max-width: 767px) {
    .sidebar-mobile {
        width: 70%; /* モバイル時のサイドバー幅 */
        transform: translateX(-100%); /* デフォルトで非表示 */
        transition: transform 0.3s ease-in-out;
        z-index: 50;
        background-color: #ffffff; /* 背景色 */
        position: absolute;
        height: 100%;
    }
    .sidebar-mobile.visible {
        transform: translateX(0); /* 表示 */
    }

    /* オーバーレイスタイル */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* 背景を半透明に */
        z-index: 40;
        transition: opacity 0.3s ease-in-out;
    }
}

/* iPadサイズ専用（768px ～ 1024px） */
@media (min-width: 768px) and (max-width: 1024px) {
    .sidebar-mobile {
        width: 220px; /* iPadサイズではサイドバーを狭くする */
    }

    .flex-1 {
        margin-left: 220px; /* サイドバーの幅分余白を調整 */
    }
}

/* iPad Proサイズ以上（1024px以上） */
@media (min-width: 1025px) {
    .sidebar-mobile {
        width: 250px; /* 通常のサイドバー幅 */
    }

    .flex-1 {
        margin-left: 250px; /* 通常の余白 */
    }
}

/* 全画面表示専用（デスクトップサイズ以上） */
@media (min-width: 1366px) {
    .flex-1 {
        margin-left: 280px; /* サイドバー幅より広い余白を設定 */
    }
}

/* モバイルサイズのトグルボタン */
@media (max-width: 767px) {
    .toggle-button {
        display: block; /* 767px以下でトグルボタンを表示 */
    }
}

/* デスクトップサイズのトグルボタン */
@media (min-width: 768px) {
    .toggle-button {
        display: none; /* 768px以上では非表示 */
    }
}

@media (min-width: 640px) and (max-width: 767px) {
    .sidebar-mobile {
        margin-top: 0 !important; /* この範囲ではmt-16を無効化 */
        width: 50% !important;
    }
}
</style>
