<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue";

const props = defineProps({
    postId: {
        type: Number,
        required: true,
    },
    parentId: {
        type: Number,
        default: null,
    },
    selectedForumId: {
        type: Number,
        required: true,
    },
    replyToName: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "返信",
    },
});

const emit = defineEmits(["cancel"]);
const message = ref("");
const placeholder = ref(`@${props.replyToName} さんへの返信を入力`);

// コメントデータを管理するref
const commentData = ref({
    post_id: props.postId,
    parent_id: props.parentId,
    message: "",
    replyToName: props.replyToName,
});

// 画像関連のrefを追加
const image = ref(null);
const imagePreview = ref(null);
const fileInput = ref(null);
const isModalOpen = ref(false);
const localErrorMessage = ref(null);

onMounted(() => {
    // コメントに対する返信の場合のみメンションを追加
    if (props.replyToName && props.parentId) {
        commentData.value.message = `@${props.replyToName} `;
    }
});

// 画像ファイルのチェック
const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        const validImageTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp",
        ];
        if (!validImageTypes.includes(file.type)) {
            localErrorMessage.value =
                "対応していないファイル形式です。png, jpg, gif, webpのいずれかを選択してください。";
            setTimeout(() => {
                localErrorMessage.value = null;
            }, 8000);
            return;
        }
        image.value = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

// ファイル選択ボタンをクリックしたときの処理
const triggerFileInput = () => {
    fileInput.value.click();
};

// 画像を削除する処理
const removeImage = () => {
    image.value = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// コメントの送信処理に画像データを追加
const submitComment = () => {
    commentData.value._token = getCsrfToken();
    commentData.value.post_id = props.postId;

    const formData = new FormData();
    formData.append("message", commentData.value.message);
    formData.append("post_id", commentData.value.post_id);
    formData.append("_token", commentData.value._token);

    if (image.value) {
        formData.append("image", image.value);
    }

    router.post(route("comment.store", { post: props.postId }), formData, {
        preserveScroll: true,
        onSuccess: () => {
            commentData.value = {
                post_id: props.postId,
                parent_id: props.parentId,
                message: "",
            };
            image.value = null;
            imagePreview.value = null;
            router.visit(
                route("forum.index", { forum_id: props.selectedForumId }),
                {
                    preserveScroll: true,
                    replace: true,
                }
            );
        },
        onError: (errors) => {
            console.error("コメントの投稿に失敗しました:", errors);
        },
    });
};

// キャンセルハンドラーを追加
const handleCancel = () => {
    // フォームをリセット
    commentData.value = {
        post_id: props.postId,
        parent_id: props.parentId,
        message: "",
        replyToName: props.replyToName,
    };
    // 親コンポーネントにキャンセルイベントを発行
    emit("cancel");
};
</script>

<template>
    <div class="mt-4">
        <form @submit.prevent="submitComment" enctype="multipart/form-data">
            <div class="relative">
                <textarea
                    v-model="commentData.message"
                    class="w-full p-2 border rounded-md"
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
            <input
                type="file"
                accept="image/*"
                ref="fileInput"
                @change="handleImageChange"
                style="display: none"
            />
            <!-- エラーメッセージ表示 -->
            <div v-if="localErrorMessage" class="text-red-500 mt-2">
                {{ localErrorMessage }}
            </div>
            <!-- プレビュー表示 -->
            <div v-if="imagePreview" class="relative mt-2 inline-block">
                <img
                    :src="imagePreview"
                    alt="画像プレビュー"
                    class="w-32 h-32 object-cover rounded-md cursor-pointer hover:opacity-80 transition"
                    @click="isModalOpen = true"
                />
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
            <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                <img
                    :src="imagePreview"
                    class="max-w-full max-h-full rounded-lg"
                />
            </ImageModal>
            <div class="flex justify-end space-x-2 mt-2">
                <button
                    type="button"
                    @click="handleCancel"
                    class="my-2 py-2 px-4 rounded-md bg-gray-300 text-gray-700 font-medium transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
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
