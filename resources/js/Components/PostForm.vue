<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { getCsrfToken } from "@/Utils/csrf";

const props = defineProps({
    forumId: [String, Number], // String または Number 型を許容
});

const postData = ref({
    title: "",
    message: "",
    forum_id: props.forumId ? Number(props.forumId) : null, // forum_id を追加し、初期値を適切に設定
});

const image = ref(null); // 画像ファイル用
const imagePreview = ref(null); // プレビュー用
const fileInput = ref(null);

// forumIdの変更を監視し、postDataに反映
watch(
    () => props.forumId,
    (newForumId) => {
        postData.value.forum_id = newForumId ? Number(newForumId) : null;
    }
);

// 画像選択時の処理
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        image.value = file;
        imagePreview.value = URL.createObjectURL(file); // プレビュー用URLを生成
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

// 投稿の送信処理（画像やファイルを添付に対応）
const submitPost = () => {
    if (!postData.value.forum_id || postData.value.forum_id === 0) {
        console.error("有効な掲示板IDが選択されていません。");
        return;
    }

    const formData = new FormData();
    formData.append("title", postData.value.title);
    formData.append("message", postData.value.message);
    formData.append("forum_id", postData.value.forum_id);
    formData.append("_token", getCsrfToken());

    if (image.value) {
        formData.append("image", image.value); // 画像データの追加
    }

    router.post(route("forum.store"), formData, {
        onSuccess: () => {
            postData.value = {
                title: "",
                message: "",
                forum_id: props.forumId,
            };
            image.value = null;
            imagePreview.value = null;
            router.get(
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
        <form @submit.prevent="submitPost">
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
                    class="border-gray-300 rounded-md px-2 pr-12"
                    required
                    placeholder="本文を入力してください"
                ></textarea>
                <!-- ファイル選択アイコン -->
                <div
                    class="absolute right-2 bottom-3 bg-gray-300 text-black transition hover:bg-gray-400 hover:text-white rounded-md flex items-center justify-center cursor-pointer"
                    style="width: 40px; height: 40px"
                    @click="triggerFileInput"
                >
                    <i class="bi bi-file-earmark text-xl"></i>
                </div>
                <!-- 隠しファイル入力 -->
                <input
                    type="file"
                    accept="image/*"
                    ref="fileInput"
                    @change="handleImageUpload"
                    style="display: none"
                />
            </div>

            <!-- プレビュー表示 -->
            <div v-if="imagePreview" class="mt-2">
                <img
                    :src="imagePreview"
                    alt="画像プレビュー"
                    class="w-32 h-32 object-cover rounded-md"
                />
            </div>

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
