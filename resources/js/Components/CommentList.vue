<script setup>
const props = defineProps({
    comments: Array, // 親コンポーネントからコメント一覧を受け取る
    postId: Number, // 投稿のIDを受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteComment: Function, // コメント削除の関数を親から受け取る
    toggleCommentForm: Function, // コメントフォーム表示の関数を親から受け取る
});
</script>

<template>
    <div v-if="comments.length">
        <h3 class="font-bold mb-2">コメント一覧</h3>
        <div v-for="comment in comments" :key="comment.id" class="ml-4 mb-2">
            <p class="text-xs">
                {{ comment.created_at }}
                <span v-if="comment.user">＠{{ comment.user.name }}</span>
                <span v-else>＠Unknown</span>
            </p>
            <p>{{ comment.message }}</p>

            <!-- 返信ボタン -->
            <button
                @click="
                    toggleCommentForm(
                        postId,
                        comment.id,
                        comment.user ? comment.user.name : 'Unknown'
                    )
                "
                class="px-2 py-1 rounded bg-green-500 text-white font-bold link-hover cursor-pointer"
            >
                <i class="bi bi-reply"></i>
            </button>

            <!-- コメント削除ボタン -->
            <button
                v-if="isCommentAuthor(comment)"
                @click="deleteComment(postId, comment.id)"
                class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
            >
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
.link-hover:hover {
    opacity: 70%;
}
</style>
