<script setup>
import CommentForm from "./CommentForm.vue"; // CommentFormをインポート

const props = defineProps({
    childComments: Array, // 子コメントリスト
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    deleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
    openUserProfile: Function, // ユーザープロフィールを開く関数
});
</script>

<template>
    <div v-if="childComments.length">
        <div v-for="comment in childComments" :key="comment.id" class="mb-4">
            <div class="ml-4 mb-2 pl-2">
                <p class="text-xs flex items-center space-x-2">
                    {{ formatDate(comment.created_at) }}

                    <!-- ユーザーアイコンの表示 -->
                    <img
                        v-if="comment.user && comment.user.icon"
                        :src="
                            comment.user.icon.startsWith('/storage/')
                                ? comment.user.icon
                                : `/storage/${comment.user.icon}`
                        "
                        alt="User Icon"
                        class="w-6 h-6 rounded-full cursor-pointer"
                        @click="openUserProfile(comment)"
                    />
                    <img
                        v-else
                        src="https://via.placeholder.com/40"
                        alt="Default Icon"
                        class="w-6 h-6 rounded-full cursor-pointer"
                        @click="openUserProfile(comment)"
                    />

                    <!-- 投稿者名の表示 -->
                    <span
                        v-if="comment.user"
                        @click="openUserProfile(comment)"
                        class="hover:bg-blue-300 p-1 rounded cursor-pointer"
                    >
                        @{{ comment.user.name }}
                    </span>
                    <span v-else>＠Unknown</span>
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
                        title="返信"
                    >
                        <i class="bi bi-reply"></i>
                    </button>

                    <!-- コメント削除ボタン -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="deleteItem('comment', comment.id)"
                        class="px-2 py-1 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                        title="コメントの削除"
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
            </div>

            <!-- 子コメントをフラットに表示-->
            <div v-if="comment.children && comment.children.length">
                <ChildComment
                    :child-comments="comment.children"
                    :postId="postId"
                    :formatDate="formatDate"
                    :isCommentAuthor="isCommentAuthor"
                    :deleteItem="deleteItem"
                    :toggleCommentForm="toggleCommentForm"
                    :commentFormVisibility="commentFormVisibility"
                    :openUserProfile="openUserProfile"
                />
            </div>
        </div>
    </div>
</template>
