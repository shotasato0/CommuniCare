<script setup>
import { ref } from "vue";
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
        <div v-for="comment in comments" :key="comment.id" class="mb-4">
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
                    <button
                        @click="toggleCollapse(comment.id)"
                        class="text-blue-500"
                    >
                        <!-- 折りたたみ状態に応じてアイコンとテキストを切り替える -->
                        <span v-if="collapsedComments[comment.id]">
                            <i class="bi bi-caret-up-fill"></i>
                            {{
                                getCommentCountRecursive(comment.children)
                            }}件の返信
                        </span>
                        <span v-else>
                            <i class="bi bi-caret-down-fill"></i>
                            {{
                                getCommentCountRecursive(comment.children)
                            }}件の返信
                        </span>
                    </button>
                </div>

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

                <!-- 子コメントに対する返信フォーム -->
                <CommentForm
                    v-if="
                        commentFormVisibility[postId]?.[comment.id]?.isVisible
                    "
                    :postId="postId"
                    :parentId="comment.id"
                    :replyToName="comment.parent_id ? comment.user?.name : ''"
                    class="mt-4"
                />

                <!-- 子コメントを折りたたみ・展開 -->
                <div
                    v-if="
                        comment.children &&
                        comment.children.length &&
                        collapsedComments[comment.id]
                    "
                    class="ml-4"
                >
                    <ChildComment
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
    </div>
</template>
