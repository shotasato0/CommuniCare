<script setup>
const props = defineProps({
    comment: Object, // 親コンポーネントから渡される単一のコメントデータ（オブジェクト型）
    postId: Number, // 投稿のIDを受け取る
    formatDate: Function, // 日付をフォーマットする関数を親から受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteItem: Function, // コメント削除の関数を親から受け取る
});
</script>

<template>
    <div class="ml-4 mb-2">
        <p class="text-xs">
            {{ formatDate(comment.created_at) }} ＠{{
                comment.user?.name || "Unknown"
            }}
        </p>
        <p>{{ comment.message }}</p>

        <!-- コメント削除ボタン -->
        <button
            v-if="isCommentAuthor(comment)"
            @click="deleteItem('comment', comment.id)"
            class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>
</template>
