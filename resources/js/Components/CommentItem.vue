<script setup>
const props = defineProps({
    comment: Object, // 親コンポーネントから渡される単一のコメントデータ（オブジェクト型）
    postId: Number, // 親コンポーネントから渡される投稿ID（数値型）。コメントが属する投稿を特定するために使用
    isCommentAuthor: Function, // コメントの作成者かどうかを判定する関数。親コンポーネントから渡される
    deleteComment: Function, // コメントを削除する関数。親コンポーネントから渡される
    toggleCommentForm: Function, // コメントフォームの表示/非表示を切り替える関数。親コンポーネントから渡される
});
</script>

<template>
    <div class="ml-4 mb-2">
        <p class="text-xs">
            {{ comment.created_at }} ＠{{ comment.user?.name || "Unknown" }}
        </p>
        <p>{{ comment.message }}</p>

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
            @click="deleteComment(postId, comment.id)"
            class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>
</template>
