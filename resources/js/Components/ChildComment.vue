<script setup>
const props = defineProps({
    childComments: Array, // 子コメントリスト
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    deleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
});
</script>

<template>
    <div v-if="childComments.length">
        <div
            v-for="comment in childComments"
            :key="comment.id"
            class="ml-8 mb-2"
        >
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
                            comment.parent_id,
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
        </div>
    </div>
</template>
