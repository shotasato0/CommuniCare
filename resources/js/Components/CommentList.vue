<script setup>
import CommentItem from "@/Components/CommentItem.vue";

const props = defineProps({
    comments: Array, // 親コンポーネントからコメント一覧を受け取る
    postId: Number, // 投稿のIDを受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteItem: Function, // コメント削除の関数を親から受け取る
    toggleCommentForm: Function, // コメントフォーム表示の関数を親から受け取る
});
</script>

<template>
    <div v-if="comments.length">
        <h3 class="font-bold mb-2">コメント一覧</h3>
        <CommentItem
            v-for="comment in comments"
            :key="comment.id"
            :comment="comment"
            :postId="postId"
            :isCommentAuthor="isCommentAuthor"
            :deleteItem="deleteItem"
            :toggleCommentForm="toggleCommentForm"
        />
    </div>
</template>
