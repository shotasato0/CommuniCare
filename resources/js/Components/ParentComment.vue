<script setup>
import ChildComment from "./ChildComment.vue";
import CommentForm from "./CommentForm.vue"; // CommentFormをインポート

const props = defineProps({
    comments: Array, // 親コメントからのコメントデータ
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    deleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
});
</script>

<template>
    <div v-if="comments.length">
        <div v-for="comment in comments" :key="comment.id" class="mb-4">
            <div class="ml-4 mb-2 border-l-2 border-gray-300 pl-2">
                <p class="text-xs">
                    {{ formatDate(comment.created_at) }} ＠{{
                        comment.user?.name || "Unknown"
                    }}
                </p>
                <p>{{ comment.message }}</p>

                <div class="flex justify-end space-x-2 mt-2">
                    <!-- 返信ボタン -->
                    <button
                        @click="
                            toggleCommentForm(
                                postId,
                                comment.id,
                                comment.user?.name || 'Unknown'
                            )
                        "
                        class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
                    >
                        <i class="bi bi-reply"></i>
                    </button>

                    <!-- コメント削除ボタン -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="deleteItem('comment', comment.id)"
                        class="px-2 py-1 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <!-- 返信フォーム -->
                <CommentForm
                    v-if="
                        commentFormVisibility[postId]?.[comment.id]?.isVisible
                    "
                    :postId="postId"
                    :parentId="comment.id"
                    :replyToName="
                        commentFormVisibility[postId]?.[comment.id]?.replyToName
                    "
                    class="mt-4"
                />
                <!-- 子コメントビュー -->
                <ChildComment
                    v-if="comment.children && comment.children.length"
                    :child-comments="comment.children"
                    :postId="postId"
                    :formatDate="formatDate"
                    :isCommentAuthor="isCommentAuthor"
                    :deleteItem="deleteItem"
                    :toggleCommentForm="toggleCommentForm"
                    :commentFormVisibility="commentFormVisibility"
                />
            </div>
        </div>
    </div>
</template>
