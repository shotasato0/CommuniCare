<script setup>
import { ref } from "vue";
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
});

// 子コメントの折りたたみ状態を管理
const collapsedComments = ref({});

// 子コメントの表示・非表示を切り替える関数
const toggleCollapse = (commentId) => {
    collapsedComments.value[commentId] = !collapsedComments.value[commentId];
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
                        class="w-8 h-8 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />
                    <img
                        v-else
                        src="https://via.placeholder.com/40"
                        alt="Default Icon"
                        class="w-8 h-8 rounded-full border border-gray-300 shadow-sm cursor-pointer hover:scale-110 transition-transform duration-300"
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
                <p class="mt-3">{{ comment.message }}</p>

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
                        class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer flex items-center"
                        title="返信"
                    >
                        <i class="bi bi-reply"></i>
                    </button>
                    <!-- コメント削除 -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="onDeleteItem('comment', comment.id)"
                        class="px-2 py-1 rounded bg-red-500 text-white font-bold link-hover cursor-pointer flex items-center"
                        title="コメント削除"
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
                    :replyToName="comment.user?.name || ''"
                    class="mt-4"
                    title="返信"
                />

                <!-- 子コメント表示 -->
                <div
                    v-if="
                        comment.children &&
                        comment.children.length > 0 &&
                        collapsedComments[comment.id]
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
                    />
                </div>
            </div>
        </div>
    </div>
</template>
