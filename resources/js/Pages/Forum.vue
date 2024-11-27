<script setup>
import { ref, onMounted, watch } from "vue";
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
import ListForSidebar from "./Unit/ListForSidebar.vue";
import RightSidebar from "./Unit/RightSidebar.vue";
import LikeButton from "@/Components/LikeButton.vue";
import QuotePostForm from "@/Components/QuotePostForm.vue";

// propsからページのデータを取得
const pageProps = usePage().props; // ページのデータ
const posts = ref(pageProps.posts || { data: [], links: [] }); // 投稿のデータ
const auth = pageProps.auth; // ログインユーザー情報
const units = ref(pageProps.units || []); // 部署のデータ
const selectedPost = ref(null); // 選択された投稿
const isUserProfileVisible = ref(false); // ユーザーの詳細ページの表示状態
const sidebarVisible = ref(false); // サイドバーの表示状態
const users = pageProps.users || []; // ユーザーのデータ
const sidebar = ref(null); // サイドバーのコンポーネントインスタンス
const selectedForumId = ref(pageProps.selectedForumId || null); // 選択された掲示板のID
const selectedUnitUsers = ref([]); // 選択されたユニットのユーザーリスト
const selectedUnitName = ref(""); // 選択されたユニットの名前
const search = ref(pageProps.search || ""); // 検索結果の表示状態
const quotedPost = ref(null);
const showPostForm = ref(false); // 引用投稿フォームの表示制御

const quotePost = (post) => {
    console.log("quotePost called with:", post); // 確認用ログ
    quotedPost.value = post; // post全体をセットする
    showPostForm.value = true;
    console.log("quotedPost.value:", quotedPost.value);
    console.log("showPostForm.value:", showPostForm.value);
};

// マウント時にselectedForumIdを設定
onMounted(() => {
    selectedForumId.value = pageProps.selectedForumId;
});

onMounted(() => {
    const storedUsers = sessionStorage.getItem("selectedUnitUsers");

    // `storedUsers`がnullや"undefined"ではなく、有効なJSONかをチェック
    if (storedUsers && storedUsers !== "undefined") {
        try {
            selectedUnitUsers.value = JSON.parse(storedUsers);
        } catch (error) {
            console.error("Error parsing selectedUnitUsers:", error);
            selectedUnitUsers.value = []; // パースエラー時には空配列を代入
        }
    } else {
        selectedUnitUsers.value = []; // nullまたは"undefined"の場合は空配列を代入
    }

    // 保存されたユニット名を復元
    const storedUnitName = sessionStorage.getItem("selectedUnitName");
    selectedUnitName.value = storedUnitName || ""; // nullの場合は空文字列を代入
});

// selectedForumIdの変更を監視し、変更があるたびに投稿を再取得
watch(selectedForumId, (newForumId) => {
    if (newForumId) {
        router.get(route("forum.index", { forum_id: newForumId }), {
            preserveState: true,
            only: ["posts"],
        });
    }
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
    if (unit && unit.forum) {
        selectedForumId.value = unit.forum.id;
        selectedUnitName.value = unit.name; // 選択されたユニットの名前を設定
        localStorage.setItem("lastSelectedUnitId", unitId);

        // ユニット名を保存
        sessionStorage.setItem("selectedUnitName", selectedUnitName.value);

        // ユーザーリストを取得して一時保存
        selectedUnitUsers.value = users.filter(
            (user) => user.unit_id === unitId
        );
        sessionStorage.setItem(
            "selectedUnitUsers",
            JSON.stringify(selectedUnitUsers.value)
        );

        // 掲示板を更新
        router.get(route("forum.index", { forum_id: selectedForumId.value }), {
            preserveState: false, // 状態を再レンダリング
        });
    } else {
        console.error("対応する掲示板が見つかりませんでした");
    }
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

// 投稿を選択する関数
const selectPost = (post) => {
    selectedPost.value = post;
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
                        class="text-xl font-bold cursor-pointer sm:hidden"
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
                />

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

                        <!-- 引用投稿がある場合の表示 -->
                        <div
                            v-if="post.quoted_post"
                            class="quoted-post mb-2 p-2 border-l-4 border-gray-300 bg-gray-100"
                        >
                            <div class="original-post">
                                <div class="flex items-center space-x-2">
                                    <img
                                        v-if="post.quoted_post.user.icon"
                                        :src="
                                            post.quoted_post.user.icon.startsWith(
                                                '/storage/'
                                            )
                                                ? post.quoted_post.user.icon
                                                : `/storage/${post.quoted_post.user.icon}`
                                        "
                                        alt="User Icon"
                                        class="w-6 h-6 rounded-full cursor-pointer mb-1"
                                        @click="
                                            openUserProfile(post.quoted_post)
                                        "
                                    />
                                    <img
                                        v-else
                                        src="https://via.placeholder.com/40"
                                        alt="Default Icon"
                                        class="w-6 h-6 rounded-full cursor-pointer"
                                        @click="
                                            openUserProfile(post.quoted_post)
                                        "
                                    />
                                    <span
                                        @click="
                                            openUserProfile(post.quoted_post)
                                        "
                                        class="hover:bg-blue-300 p-1 rounded cursor-pointer"
                                    >
                                        ＠{{ post.quoted_post.user.name }}
                                    </span>
                                </div>
                                <p class="text-sm mb-2 font-bold">
                                    {{ post.quoted_post.title }}
                                </p>
                                <p class="text-sm mb-2">
                                    {{ post.quoted_post.message }}
                                </p>
                            </div>
                        </div>

                        <p class="mb-2">{{ post.message }}</p>

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
</style>
