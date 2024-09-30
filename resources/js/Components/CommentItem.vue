<script setup>
import CommentList from "@/Components/CommentList.vue";
import { onMounted } from "vue";

const props = defineProps({
    comment: Object, // 親コンポーネントから渡される単一のコメントデータ（オブジェクト型）
    postId: Number, // 投稿のIDを受け取る
    formatDate: Function, // 日付をフォーマットする関数を親から受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteItem: Function, // コメント削除の関数を親から受け取る
    toggleCommentForm: Function, // コメントフォームを表示する関数を親から受け取る
});

// コンポーネントがマウントされたときにcomment.childrenの内容をログに表示
onMounted(() => {
//    console.log(props.comment);
   console.log(props.comment.user);
});
</script>

<template>
    <div class="ml-4 mb-2 border-l-2 border-gray-300 pl-2">
        <p class="text-xs">
            {{ formatDate(comment.created_at) }} ＠{{
                comment.user?.name || "Unknown"
            }}
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
            @click="deleteItem('comment', comment.id)"
            class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
        >
            <i class="bi bi-trash"></i>
        </button>

        <!-- 子コメントの表示 -->
        <div
            v-if="comment.children && comment.children.length"
            class="ml-6 mt-2"
        >
            <CommentList
                :comments="comment.children"
                :postId="postId"
                :formatDate="formatDate"
                :isCommentAuthor="isCommentAuthor"
                :deleteItem="deleteItem"
                :toggleCommentForm="toggleCommentForm"
            />
        </div>
    </div>
</template>
