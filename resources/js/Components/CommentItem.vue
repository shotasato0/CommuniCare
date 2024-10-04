<script setup>
import CommentList from "@/Components/CommentList.vue";
import CommentForm from "@/Components/CommentForm.vue";
import { ref } from "vue";

const props = defineProps({
    comment: Object, // 親コンポーネントから渡される単一のコメントデータ（オブジェクト型）
    postId: Number, // 投稿のIDを受け取る
    formatDate: Function, // 日付をフォーマットする関数を親から受け取る
    isCommentAuthor: Function, // コメントの作者かどうかを確認する関数
    deleteItem: Function, // コメント削除の関数を親から受け取る
    toggleCommentForm: Function, // コメントフォームを表示する関数を親から受け取る
    commentFormVisibility: Object, // コメントフォームの表示状態を親から受け取る
});

// 折りたたみ状態を管理するための状態をコメントごとに保持
const collapsedComments = ref({});

// コメントを折りたたむ・展開する関数
const toggleCollapse = (commentId) => {
    collapsedComments.value[commentId] = !collapsedComments.value[commentId];
};

// 再帰的にすべての子コメントをカウントする関数
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
    <div class="ml-4 mb-2 border-l-2 border-gray-300 pl-2">
        <p class="text-xs">
            {{ formatDate(comment.created_at) }} ＠{{
                comment.user?.name || "Unknown"
            }}
        </p>
        <p>{{ comment.message }}</p>

        <!-- 子コメントの数を表示し、折りたたみ機能を追加 -->
        <div
            v-if="comment.children && comment.children.length > 0"
            class="mt-2"
        >
            <!-- メッセージの件数を表示 -->
            <button @click="toggleCollapse(comment.id)" class="text-blue-500">
                <!-- 折りたたみ状態に応じてアイコンとテキストを切り替える -->
                <span v-if="collapsedComments[comment.id]">
                    <i class="bi bi-caret-up-fill"></i>
                    {{ getCommentCountRecursive(comment.children) }}件の返信
                </span>
                <span v-else>
                    <i class="bi bi-caret-down-fill"></i>
                    {{ getCommentCountRecursive(comment.children) }}件の返信
                </span>
            </button>

            <!-- 子コメントを折りたたむ・展開 -->
            <div v-if="collapsedComments[comment.id]" class="ml-4">
                <CommentList
                    :comments="comment.children"
                    :postId="postId"
                    :formatDate="formatDate"
                    :isCommentAuthor="isCommentAuthor"
                    :deleteItem="deleteItem"
                    :toggleCommentForm="toggleCommentForm"
                    :commentFormVisibility="commentFormVisibility"
                />
            </div>
        </div>

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

        <!-- コメントに対する返信フォーム -->
        <CommentForm
            v-if="
                commentFormVisibility[postId] &&
                commentFormVisibility[postId][comment.id]?.isVisible
            "
            :postId="postId"
            :parentId="comment.id"
            :replyToName="
                commentFormVisibility[postId]?.[comment.id]?.replyToName
            "
            class="mt-4"
        />
    </div>
</template>
