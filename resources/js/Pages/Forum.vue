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

const quotePost = (post) => {
    quotedPost.value = post; // post全体をセットする
    showPostForm.value = true;
};

onMounted(() => {
    // マウント時にselectedForumIdを初期化
    initSelectedForumId(selectedForumId);
    // マウント時に選択されたユニットのユーザーと名前を復元
    restoreSelectedUnit(selectedUnitUsers, selectedUnitName);
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

    // サイドバーを非表示にする際にドロップダウンを閉じる
    if (!sidebarVisible.value) {
        if (sidebar.value && sidebar.value.resetDropdown) {
            sidebar.value.resetDropdown();
        } else {
            console.error(
                "Sidebar component reference not found or resetDropdown method is undefined."
            );
        }
    }
};

// コメントフォーム表示状態を管理するためのオブジェクト
const commentFormVisibility = ref({});

// コメントフォームの表示・非表示を切り替える関数
const toggleCommentForm = (postId, parentId = "post", replyToName = "") => {
    commentFormVisibility.value[postId] ??= {}; // 投稿IDが存在しない場合は空のオブジェクトを初期化
    commentFormVisibility.value[postId][parentId] ??= {
        // 親IDが存在しない場合は初期化
        isVisible: false,
        replyToName: "",
    };

    // コメントフォームの表示・非表示を切り替え
    commentFormVisibility.value[postId][parentId].isVisible =
        !commentFormVisibility.value[postId][parentId].isVisible;
    commentFormVisibility.value[postId][parentId].replyToName = replyToName;
};

const onDeleteItem = (type, id) => {
    deleteItem(type, id, (deletedId) => {
        if (type === "post") {
            posts.value.data = posts.value.data.filter(
                (post) => post.id !== deletedId
            );
        } else if (type === "comment") {
            handleCommentDeletion(deletedId);
        }

        router.get(route("forum.index"), {
            preserveState: false,
            preserveScroll: true,
        });
    });
};

// コメント削除を処理する関数
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
            console.log(`コメント削除成功: ${commentId}`);
        } else {
            console.error(`コメント削除に失敗しました: ${commentId}`);
        }
    } else {
        console.error(`削除対象のコメントが見つかりませんでした: ${commentId}`);
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
                class="sidebar-mobile p-4 sm:mt-16 lg:block"
                :class="{ visible: sidebarVisible }"
                ref="sidebar"
                @user-profile-clicked="onUserSelected"
                v-model:sidebarVisible="sidebarVisible"
                @forum-selected="onForumSelected"
            />

            <!-- メインコンテンツエリア -->
            <div class="flex-1 max-w-4xl mx-auto p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1
                        class="text-xl font-bold cursor-pointer toggle-button"
                        @click="toggleSidebar"
                    >
                        {{ $t("Unit List") }}
                    </h1>

                    <!-- 検索フォーム -->
                    <SearchForm
                        :selected-forum-id="selectedForumId"
                        class="ml-auto"
                    />
                </div>

                <!-- 検索結果 -->
                <div v-if="search" class="text-lg font-bold mb-4">
                    <p>検索結果: {{ posts.total }}件</p>
                </div>

                <!-- 上部ページネーション -->
                <Pagination
                    :links="posts?.links || []"
                    @change="onPageChange"
                    class="mb-4"
                />

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
                                class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                                title="返信"
                            >
                                <i class="bi bi-reply"></i>
                            </button>
                            <!-- 引用投稿ボタン -->
                            <button
                                type="button"
                                @click="quotePost(post)"
                                class="px-2 py-1 rounded bg-blue-500 text-white font-bold link-hover cursor-pointer flex items-center"
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
                                class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
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
                            :replyToName="
                                commentFormVisibility[post.id]?.['post']
                                    ?.replyToName
                            "
                            class="mt-4"
                            title="返信"
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
                        :onDeleteItem="onDeleteItem"
                        :toggleCommentForm="toggleCommentForm"
                        :commentFormVisibility="commentFormVisibility"
                        :openUserProfile="openUserProfile"
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
.link-hover:hover {
    opacity: 70%;
}

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
