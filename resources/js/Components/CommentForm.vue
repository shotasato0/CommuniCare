<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue";
import { handleImageChange } from "@/Utils/imageHandler";

// コメントフォームのプロパティを定義
const props = defineProps({
    postId: {
        // 投稿ID
        type: Number,
        required: true,
    },
    parentId: {
        // 親コメントID
        type: Number,
        default: null,
    },
    selectedForumId: {
        // 選択中の掲示版ID
        type: Number,
        required: true,
    },
    replyToName: {
        // 返信先のユーザー名
        type: String,
        default: "",
    },
    title: {
        // フォームのタイトル
        type: String,
        default: "返信",
    },
});

const emit = defineEmits(["cancel"]); // キャンセルイベントを発行するためのemit
const placeholder = ref(`@${props.replyToName} さんへの返信を入力`); // プレースホルダー

// コメントデータを管理するref
const commentData = ref({
    // コメントデータ
    post_id: props.postId, // 投稿ID
    parent_id: props.parentId, // 親コメントID
    message: "", // コメントメッセージ
    replyToName: props.replyToName, // 返信先のユーザー名
});

// 画像関連のrefを追加
const img = ref(null); // 画像ファイル
const imgPreview = ref(null); // 画像プレビュー
const fileInput = ref(null); // ファイル選択ボタン
const isModalOpen = ref(false); // モーダル表示
const localErrorMessage = ref(null); // エラーメッセージ

// コンポーネントのマウント時に初期値を設定
onMounted(() => {
    // コメントに対する返信の場合のみメンションを追加
    if (props.replyToName && props.parentId) {
        commentData.value.message = `@${props.replyToName} `;
    }
});

// 画像ファイルのチェック
const onImageChange = (event) => {
    handleImageChange(event, img, imgPreview, localErrorMessage);
};

// ファイル選択ボタンをクリックしたときの処理
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する処理
const removeImage = () => {
    img.value = null;
    imgPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// コメントの送信処理に画像データを追加
const submitComment = () => {
    commentData.value._token = getCsrfToken(); // CSRFトークンを追加
    commentData.value.post_id = props.postId; // 投稿IDを追加

    // フォームデータを作成
    const formData = new FormData();
    formData.append("message", commentData.value.message); // コメントメッセージを追加
    formData.append("post_id", commentData.value.post_id); // 投稿IDを追加
    formData.append("_token", commentData.value._token); // CSRFトークンを追加

    // 親コメントIDが存在する場合はフォームデータに追加
    if (props.parentId) {
        formData.append("parent_id", props.parentId); // 親コメントIDを追加
    }

    // 画像データが存在する場合はフォームデータに追加
    if (img.value) {
        // 画像データが存在する場合
        formData.append("img", img.value); // 画像データをフォームデータに追加
    }

    // コメントの投稿処理を実行
    router.post(route("comment.store", { post: props.postId }), formData, {
        preserveScroll: true, // スクロール位置を維持
        onSuccess: () => {
            // 投稿成功時の処理
            commentData.value = {
                post_id: props.postId, // 投稿IDをリセット
                parent_id: props.parentId, // 親コメントIDをリセット
                message: "", // コメントメッセージをリセット
            };
            img.value = null; // 画像ファイルをリセット
            imgPreview.value = null; // 画像プレビューをリセット
            router.visit(
                route("forum.index", { forum_id: props.selectedForumId }), // 掲示板にリダイレクト
                {
                    preserveScroll: true, // スクロール位置を維持
                    replace: true, // ページを置換
                }
            );
        },
        onError: (errors) => {
            // 投稿失敗時の処理
            console.error("コメントの投稿に失敗しました:", errors); // エラーメッセージをコンソールに出力
        },
    });
};

// キャンセルハンドラーを追加
const handleCancel = () => {
    // フォームをリセット
    commentData.value = {
        post_id: props.postId, // 投稿IDをリセット
        parent_id: props.parentId, // 親コメントIDをリセット
        message: "", // コメントメッセージをリセット
        replyToName: props.replyToName, // 返信先のユーザー名をリセット
    };
    // 親コンポーネントにキャンセルイベントを発行
    emit("cancel"); // キャンセルイベントを発行
};
</script>

<template>
    <div class="mt-4">
        <!-- コメントフォーム -->
        <form @submit.prevent="submitComment" enctype="multipart/form-data">
            <div class="relative">
                <!-- コメントメッセージ入力欄 -->
                <textarea
                    v-model="commentData.message"
                    class="w-full p-2 border border-gray-300 rounded-md"
                    required
                    :placeholder="placeholder"
                    rows="3"
                ></textarea>

                <!-- ファイル選択アイコン -->
                <div
                    class="absolute right-2 bottom-4 bg-gray-300 text-black transition hover:bg-gray-400 hover:text-white rounded-md flex items-center justify-center cursor-pointer"
                    style="width: 40px; height: 40px"
                    @click="triggerFileInput"
                    title="ファイルを選択"
                >
                    <i class="bi bi-card-image text-2xl"></i>
                </div>
            </div>

            <!-- 隠しファイル入力 -->
            <input
                type="file"
                accept="image/*"
                ref="fileInput"
                @change="onImageChange"
                style="display: none"
            />
            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 mt-2">
                {{ localErrorMessage }}
            </div>
            <!-- プレビュー表示 -->
            <div v-if="imgPreview" class="relative mt-2 inline-block">
                <!-- プレビュー画像 -->
                <img
                    :src="imgPreview"
                    alt="画像プレビュー"
                    class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                    @click="isModalOpen = true"
                />
                <!-- プレビュー画像削除ボタン -->
                <div
                    class="absolute top-0 right-0 bg-white rounded-full p-1 cursor-pointer flex items-center justify-center"
                    @click="removeImage"
                    title="画像を削除"
                    style="width: 24px; height: 24px"
                >
                    <i
                        class="bi bi-x-circle text-black hover:text-gray-500"
                    ></i>
                </div>
            </div>

            <!-- コメント画像モーダル -->
            <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                <img
                    :src="imgPreview"
                    class="max-w-full max-h-full rounded-lg"
                />
            </ImageModal>

            <!-- コメントフォームボタン群 -->
            <div class="flex justify-end space-x-2 mt-2">
                <!-- キャンセルボタン -->
                <button
                    type="button"
                    @click="handleCancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 text-gray-700 font-medium transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- コメント送信ボタン -->
                <button
                    type="submit"
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
