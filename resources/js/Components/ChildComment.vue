<script setup>
import CommentForm from "./CommentForm.vue"; // CommentFormをインポート

const props = defineProps({
    childComments: Array, // 子コメントリスト
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    onDeleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
    openUserProfile: Function, // ユーザープロフィールを開く関数
    selectedForumId: Number, // 選択されたフォーラムのID
});
</script>

<template>
    <div v-if="childComments.length">
        <div v-for="comment in childComments" :key="comment.id" class="mb-4">
            <div class="ml-4 mb-2 pl-2">
                <!-- 日付 -->
                <p class="text-xs text-gray-500">
                    {{ formatDate(comment.created_at) }}
                </p>

                <!-- プロフィール画像とユーザー名 -->
                <div class="flex items-center space-x-2 mt-1">
                    <img
                        v-if="comment.user && comment.user.icon"
                        :src="
                            comment.user.icon.startsWith('/storage/')
                                ? comment.user.icon
                                : `/storage/${comment.user.icon}`
                        "
                        alt="User Icon"
                        class="w-8 h-8 rounded-full cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />
                    <img
                        v-else
                        src="/images/default_user_icon.png"
                        alt="Default Icon"
                        class="w-6 h-6 rounded-full cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />

                    <span
                        v-if="comment.user"
                        @click="openUserProfile(comment)"
                        class="hover:bg-blue-100 p-1 rounded cursor-pointer font-semibold text-sm"
                    >
                        ＠{{ comment.user.name }}
                    </span>
                    <span v-else class="italic text-sm">＠Unknown</span>
                </div>

                <!-- コメント本文 -->
                <p class="mt-2 whitespace-pre-wrap">{{ comment.message }}</p>

                <!-- コメント画像 -->
                <div v-if="comment.img" class="mt-3">
                    <img
                        :src="`/storage/${comment.img}`"
                        alt="添付画像"
                        class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                        @click="openModal(`/storage/${comment.img}`)"
                    />
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
                        class="px-4 py-2 rounded-md bg-green-100 text-green-700 transition hover:bg-green-300 hover:text-white cursor-pointer flex items-center"
                        title="返信"
                    >
                        <i class="bi bi-reply"></i>
                    </button>

                    <!-- 返信削除ボタン -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="onDeleteItem('comment', comment.id)"
                        class="px-4 py-2 rounded-md bg-red-100 text-red-700 transition hover:bg-red-300 hover:text-white cursor-pointer flex items-center"
                        title="返信の削除"
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
                    :selected-forum-id="selectedForumId"
                    :replyToName="comment.user?.name || ''"
                    @cancel="toggleCommentForm(postId, comment.id)"
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
                    :onDeleteItem="onDeleteItem"
                    :toggleCommentForm="toggleCommentForm"
                    :commentFormVisibility="commentFormVisibility"
                    :openUserProfile="openUserProfile"
                    :selectedForumId="selectedForumId"
                />
            </div>
        </div>
    </div>
</template>
