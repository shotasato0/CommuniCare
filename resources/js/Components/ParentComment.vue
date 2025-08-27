<script setup>
import { ref, onMounted } from "vue";
import ChildComment from "./ChildComment.vue";
import CommentForm from "./CommentForm.vue"; // CommentFormをインポート
import LikeButton from "./LikeButton.vue";
import ImageModal from "./ImageModal.vue";
import AttachmentList from "./AttachmentList.vue";

const props = defineProps({
    comments: Array, // 親コメントからのコメントデータ
    postId: Number, // 親投稿のID
    formatDate: Function, // 日付フォーマット用関数
    isCommentAuthor: Function, // コメント作成者かを確認する関数
    onDeleteItem: Function, // コメント削除用関数
    toggleCommentForm: Function, // コメントフォームの表示切替関数
    commentFormVisibility: Object, // コメントフォームの表示状態
    openUserProfile: Function, // ユーザープロフィールを開く関数
    selectedForumId: Number, // 選択されたフォーラムのID
});

// 子コメントの折りたたみ状態を管理
const collapsedComments = ref({});

// コメント画像モーダルの表示状態を管理
const isModalOpen = ref(false);
const currentImage = ref(null);

const openModal = (imagePath) => {
    currentImage.value = imagePath;
    isModalOpen.value = true;
};

// コンポーネントのマウント時に初期値を設定
onMounted(() => {
    props.comments.forEach((comment) => {
        if (comment.children && comment.children.length > 0) {
            // 1週間（7日）をミリ秒で計算
            const oneWeek = 7 * 24 * 60 * 60 * 1000;
            const commentDate = new Date(comment.created_at);
            const now = new Date();

            // コメントが1週間以上前の場合は折りたたむ
            if (now - commentDate > oneWeek) {
                collapsedComments.value[comment.id] = false;
            } else {
                collapsedComments.value[comment.id] = true;
            }
        }
    });
});

// 子コメントの表示・非表示を切り替える関数
const toggleCollapse = (commentId) => {
    if (!(commentId in collapsedComments.value)) {
        collapsedComments.value[commentId] = true;
    } else {
        collapsedComments.value[commentId] =
            !collapsedComments.value[commentId];
    }
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
        <div
            v-for="comment in comments"
            :key="comment.id"
            class="mb-6 bg-white dark:bg-gray-800 rounded-md shadow-md p-4"
        >
            <div>
                <!-- 日付 -->
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(comment.created_at) }}
                </p>

                <!-- 投稿者名 -->
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
                        @error="$event.target.src='/images/default_user_icon.png'"
                    />
                    <img
                        v-else
                        src="/images/default_user_icon.png"
                        alt="Default Icon"
                        class="w-8 h-8 rounded-full cursor-pointer hover:scale-110 transition-transform duration-300"
                        @click="openUserProfile(comment)"
                    />

                    <span
                        v-if="comment.user"
                        @click="openUserProfile(comment)"
                        class="hover:bg-blue-100 dark:hover:bg-blue-900 p-1 rounded cursor-pointer font-semibold text-sm text-gray-800 dark:text-gray-200"
                    >
                        ＠{{ comment.user.name }}
                    </span>
                    <span v-else class="italic text-sm text-gray-600 dark:text-gray-400">＠Unknown</span>
                </div>

                <!-- コメント本文 -->
                <p
                    class="mt-3 mb-2 whitespace-pre-wrap text-gray-900 dark:text-gray-100"
                    v-html="comment.formatted_message"
                ></p>

                <!-- コメントの添付ファイル（統一システム） -->
                <div v-if="comment.attachments && comment.attachments.length > 0" class="mt-3">
                    <AttachmentList 
                        :attachments="comment.attachments"
                        :show-title="false"
                        :can-delete="false"
                    />
                </div>
                <!-- 旧システムの画像（後方互換性） -->
                <div v-else-if="comment.img" class="mt-3">
                    <img
                        :src="`/storage/${comment.img}`"
                        alt="添付画像"
                        class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                        @click="openModal(`/storage/${comment.img}`)"
                    />
                </div>

                <!-- 子コメント -->
                <div
                    v-if="comment.children && comment.children.length > 0"
                    class="mt-4"
                >
                    <button
                        @click="toggleCollapse(comment.id)"
                        class="text-blue-500 dark:text-blue-400 text-lg flex items-center link-hover"
                    >
                        <i
                            :class="
                                collapsedComments[comment.id]
                                    ? 'bi bi-caret-up-fill'
                                    : 'bi bi-caret-down-fill'
                            "
                        ></i>
                        {{ getCommentCountRecursive(comment.children) }}件の返信
                    </button>
                </div>

                <!-- ボタンを投稿下部右揃えに配置 -->
                <div class="flex justify-end space-x-2 mt-2">
                    <LikeButton
                        :likeable-id="comment.id"
                        :likeable-type="'Comment'"
                        :initial-like-count="comment.like_count"
                        :initial-is-liked="comment.is_liked_by_user"
                    />
                    <!-- 返信ボタン -->
                    <button
                        @click="
                            toggleCommentForm(
                                postId,
                                comment.id,
                                comment.user?.name || 'Unknown'
                            )
                        "
                        class="px-4 py-2 rounded-md bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 transition hover:bg-green-300 dark:hover:bg-green-600 hover:text-white cursor-pointer flex items-center"
                        title="返信"
                    >
                        <i class="bi bi-reply"></i>
                    </button>
                    <!-- コメント削除 -->
                    <button
                        v-if="isCommentAuthor(comment)"
                        @click="onDeleteItem('comment', comment.id)"
                        class="px-4 py-2 rounded-md bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 transition hover:bg-red-300 dark:hover:bg-red-600 hover:text-white cursor-pointer flex items-center"
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

                <!-- 子コメント表示 -->
                <div
                    v-if="
                        comment.children &&
                        comment.children.length > 0 &&
                        (!(comment.id in collapsedComments) ||
                            collapsedComments[comment.id])
                    "
                    class="mt-4 ml-4 border-l-2 border-gray-300 dark:border-gray-600 pl-2"
                >
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
    </div>

    <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
        <img
            :src="currentImage"
            alt="投稿画像"
            class="max-w-full max-h-full rounded-lg"
        />
    </ImageModal>
</template>
