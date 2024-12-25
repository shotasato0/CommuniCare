<script setup>
import { ref, onMounted } from "vue";
import ChildComment from "./ChildComment.vue";
import CommentForm from "./CommentForm.vue"; // CommentFormをインポート
import LikeButton from "./LikeButton.vue";

const props = defineProps({
    comments: Array, // 親コメントからのコメントデータ
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    onDeleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
    openUserProfile: Function, // ユーザープロフィールを開く関数
    selectedForumId: Number, // 選択されたフォーラムのID
});

// 子コメントの折りたたみ状態を管理
const collapsedComments = ref({});

// コンポーネントのマウント時に初期値を設定
onMounted(() => {
    props.comments.forEach((comment) => {
        if (comment.children && comment.children.length > 0) {
            // 1週間（7日）をミリ秒で計算
            const oneWeek = 7 * 24 * 60 * 60 * 1000;
            const commentDate = new Date(comment.created_at);
            const now = new Date();

            // コメントが1週間以上前の場合は折りたたむ
            if (now - commentDate > oneWeek) {
                collapsedComments.value[comment.id] = false;
            } else {
                collapsedComments.value[comment.id] = true;
            }
        }
    });
});

// 子コメントの表示・非表示を切り替える関数
const toggleCollapse = (commentId) => {
    if (!(commentId in collapsedComments.value)) {
        collapsedComments.value[commentId] = true;
    } else {
        collapsedComments.value[commentId] =
            !collapsedComments.value[commentId];
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
</script>

<template>
    <div v-if="comments.length">
        <div
            v-for="comment in comments"
            :key="comment.id"
            class="mb-6 bg-white rounded-md shadow-md p-4"
        >
            <div>
                <!-- 日付 -->
                <p class="text-xs text-gray-500">
                    {{ formatDate(comment.created_at) }}
                </p>

                <!-- 投稿者名 -->
                <div class="flex items-center space-x-2 mt-1">
                    <img
                        v-if="comment.user && comment.user.icon"
                        :src="
                            comment.user.icon.startsWith('/storage/')
                                ? comment.user.icon
                                : `/storage/${comment.user.icon}`
                        "
                        alt="User Icon"
                        class="w-8 h-8 rounded-full cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />
                    <img
                        v-else
                        src="/images/default_user_icon.png"
                        alt="Default Icon"
                        class="w-8 h-8 rounded-full cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />

                    <span
                        v-if="comment.user"
                        @click="openUserProfile(comment)"
                        class="hover:bg-blue-100 p-1 rounded cursor-pointer font-semibold text-sm"
                    >
                        ＠{{ comment.user.name }}
                    </span>
                    <span v-else class="italic text-sm">＠Unknown</span>
                </div>

                <!-- コメント本文 -->
                <p class="mt-3 whitespace-pre-wrap">{{ comment.message }}</p>

                <!-- 子コメント -->
                <div
                    v-if="comment.children && comment.children.length > 0"
                    class="mt-4"
                >
                    <button
                        @click="toggleCollapse(comment.id)"
                        class="text-blue-500 text-lg flex items-center link-hover"
                    >
                        <i
                            :class="
                                collapsedComments[comment.id]
                                    ? 'bi bi-caret-up-fill'
                                    : 'bi bi-caret-down-fill'
                            "
                        ></i>
                        {{ getCommentCountRecursive(comment.children) }}件の返信
                    </button>
                </div>

                <!-- ボタンを投稿下部右揃えに配置 -->
                <div class="flex justify-end space-x-2 mt-2">
                    <LikeButton
                        :likeable-id="comment.id"
                        :likeable-type="'Comment'"
                        :initial-like-count="comment.like_count"
                        :initial-is-liked="comment.is_liked_by_user"
                    />
                    <!-- 返信ボタン -->
                    <button
                        @click="
                            toggleCommentForm(
                                postId,
                                comment.id,
                                comment.user?.name || 'Unknown'
                            )
                        "
                        class="px-4 py-2 rounded-md bg-green-100 text-green-700 transition hover:bg-green-300 hover:text-white cursor-pointer flex items-center"
                        title="返信"
                    >
                        <i class="bi bi-reply"></i>
                    </button>
                    <!-- コメント削除 -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="onDeleteItem('comment', comment.id)"
                        class="px-4 py-2 rounded-md bg-red-100 text-red-700 transition hover:bg-red-300 hover:text-white cursor-pointer flex items-center"
                        title="返信の削除"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <!-- 子コメントに対する返信フォーム -->
                <CommentForm
                    v-if="
                        commentFormVisibility[postId]?.[comment.id]?.isVisible
                    "
                    :postId="postId"
                    :parentId="comment.id"
                    :selected-forum-id="selectedForumId"
                    :replyToName="comment.user?.name || ''"
                    @cancel="toggleCommentForm(postId, comment.id)"
                    class="mt-4"
                />

                <!-- 子コメント表示 -->
                <div
                    v-if="
                        comment.children &&
                        comment.children.length > 0 &&
                        (!(comment.id in collapsedComments) ||
                            collapsedComments[comment.id])
                    "
                    class="mt-4 ml-4 border-l-2 border-gray-300 pl-2"
                >
                    <ChildComment
                        :child-comments="comment.children"
                        :postId="postId"
                        :formatDate="formatDate"
                        :isCommentAuthor="isCommentAuthor"
                        :onDeleteItem="onDeleteItem"
                        :toggleCommentForm="toggleCommentForm"
                        :commentFormVisibility="commentFormVisibility"
                        :openUserProfile="openUserProfile"
                        :selectedForumId="selectedForumId"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
