<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";
import ImageModal from "./ImageModal.vue"; // ImageModalコンポーネントをインポート

const props = defineProps({
    forumId: [String, Number], // String または Number 型を許容
});

const postData = ref({
    title: "",
    message: "",
    forum_id: props.forumId ? Number(props.forumId) : null, // forum_id を追加し、初期値を適切に設定
});

const image = ref(null); // 画像ファイル
const imagePreview = ref(null); // 画像プレビュー
const fileInput = ref(null); // ファイル入力
const isModalOpen = ref(false); // モーダル表示
const localErrorMessage = ref(null); // エラーメッセージ

// forumIdの変更を監視し、postDataに反映
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// 画像ファイルのチェック
const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        // 画像形式のチェック（jpeg, png, gif などのみ許可）
        const validImageTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/webp",
        ];
        if (!validImageTypes.includes(file.type)) {
            localErrorMessage.value =
                "対応していないファイル形式です。png, jpg, gif, webpのいずれかを選択してください。";

            // 8秒後にエラーメッセージを自動的に消す処理
            setTimeout(() => {
                localErrorMessage.value = null;
            }, 8000);

            return;
        }

        image.value = file; // 画像ファイルを設定
        imagePreview.value = URL.createObjectURL(file); // ローカルプレビュー用にBlob URLをセット
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
        fileInput.value.value = ""; // ファイル入力の値をリセット
    }
};

// 投稿の送信処理（画像やファイルを添付に対応）
const submitPost = () => {
    if (!postData.value.forum_id || postData.value.forum_id === 0) {
        console.error("有効な掲示板IDが選択されていません。");
        return;
    }

    // フォームデータを作成
    const formData = new FormData();
    formData.append("title", postData.value.title);
    formData.append("message", postData.value.message);
    formData.append("forum_id", postData.value.forum_id);
    formData.append("_token", getCsrfToken());

    // 画像データが存在する場合、フォームデータに追加
    if (image.value) {
        formData.append("image", image.value); // 画像データの追加
    }

    // 投稿の送信
    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            // 投稿成功後の処理
            postData.value = {
                // フォームデータをリセット
                title: "",
                message: "",
                forum_id: props.forumId,
            };
            image.value = null; // 画像ファイルをリセット
            imagePreview.value = null; // 画像プレビューをリセット
            router.get(
                // 投稿成功後、掲示板のトップページにリダイレクト
                route("forum.index", { forum_id: postData.value.forum_id }),
                {
                    preserveState: true,
                }
            );
        },
        onError: (errors) => {
            console.error("投稿に失敗しました:", errors);
        },
    });
};
</script>

<template>
    <!-- 投稿フォーム -->
    <div class="bg-white rounded-md mt-5 p-3">
        <form @submit.prevent="submitPost" enctype="multipart/form-data">
            <!-- 件名 -->
            <div class="flex mt-2">
                <p class="font-medium">件名</p>
                <input
                    v-model="postData.title"
                    class="border-gray-300 rounded-md px-2 ml-2 flex-auto"
                    type="text"
                    required
                    placeholder="件名を入力してください"
                />
            </div>

            <!-- 本文 -->
            <div class="flex flex-col mt-2 relative">
                <p class="font-medium">本文</p>
                <textarea
                    v-model="postData.message"
                    class="w-full p-2 border border-gray-300 rounded-md"
                    required
                    placeholder="本文を入力してください"
                    rows="3"
                ></textarea>
                <!-- ファイル選択アイコン -->
                <div
                    class="absolute right-2 bottom-2 bg-gray-300 text-black transition hover:bg-gray-400 hover:text-white rounded-md flex items-center justify-center cursor-pointer"
                    style="width: 40px; height: 40px"
                    @click="triggerFileInput"
                    title="ファイルを選択"
                >
                    <i class="bi bi-file-earmark text-xl"></i>
                </div>
                <!-- 隠しファイル入力 -->
                <input
                    type="file"
                    accept="image/*"
                    ref="fileInput"
                    @change="handleImageChange"
                    style="display: none"
                />
            </div>

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
                <!-- 削除ボタン -->
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

            <!-- モーダル表示 -->
            <ImageModal :isOpen="isModalOpen" @close="isModalOpen = false">
                <img
                    :src="imagePreview"
                    class="max-w-full max-h-full rounded-lg"
                />
            </ImageModal>

            <!-- 送信ボタン -->
            <div class="flex justify-end mt-2">
                <button
                    class="my-2 px-4 py-2 rounded-md bg-blue-100 text-blue-700 transition hover:bg-blue-300 hover:text-white cursor-pointer"
                >
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </form>
    </div>
</template>
