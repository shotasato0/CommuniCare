<script setup>
import CommentItem from "@/Components/CommentItem.vue";

const props = defineProps({
    comments: Array, // 親コンポーネントからコメント一覧を受け取る
    postId: Number, // 投稿のIDを受け取る
    formatDate: Function, // 日付をフォーマットする関数を親から受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteItem: Function, // コメント削除の関数を親から受け取る
    toggleCommentForm: Function, // コメントフォーム表示の関数を親から受け取る
    commentFormVisibility: Object, // コメントフォームの表示状態を親から受け取る
});
</script>

<template>
    <div v-if="comments.length">
        <div v-for="comment in comments" :key="comment.id" class="mb-4">
            <CommentItem
                :comment="comment"
                :postId="postId"
                :formatDate="formatDate"
                :isCommentAuthor="isCommentAuthor"
                :deleteItem="deleteItem"
                :toggleCommentForm="toggleCommentForm"
                :commentFormVisibility="commentFormVisibility"
            />
        </div>
    </div>
</template>
