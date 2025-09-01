<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { handleImageChange } from "@/Utils/imageHandler";

const props = defineProps({
    user: Object,
});

const emit = defineEmits(["close", "updateIcon", "successMessage"]);

// アイコン編集用のフォームデータを定義
const form = useForm({
    icon: null, // アイコンを追加
});

// 画像プレビュー用の `ref` を定義
const image = ref(null);
const imagePreview = ref(null);

// アイコンのプレビューURLを定義
const previewUrl = ref(
    props.user.iconUrl || props.user.icon_url || "/images/default_user_icon.png"
);

// 成功メッセージとエラーメッセージのrefを定義
const localSuccessMessage = ref(null); // 初期値をnullに設定
const localErrorMessage = ref(null); // 初期値をnullに設定

// 画像ファイルのチェック
const onImageChange = (e) => {
    handleImageChange(e, image, imagePreview, localErrorMessage);

    // プレビューURLを更新する
    if (image.value) {
        form.icon = image.value;
        previewUrl.value = imagePreview.value;
    }
};

// フォーム送信処理
const submit = () => {
    form.post(route("profile.updateIcon"), {
        forceFormData: true,
        onSuccess: () => {
            // auth.user の最新propsからiconUrl(またはicon_url)を参照
            const pageProps = usePage().props;
            const updatedIconUrl =
                pageProps?.auth?.user?.iconUrl ||
                pageProps?.auth?.user?.icon_url ||
                "/images/default_user_icon.png";

            previewUrl.value = updatedIconUrl;
            emit("updateIcon", updatedIconUrl);
            emit("successMessage", "プロフィール画像が更新されました");

            // 成功メッセージを設定
            localSuccessMessage.value = "プロフィール画像が更新されました";

            // 8秒後にサクセスメッセージを自動的に消す
            setTimeout(() => {
                localSuccessMessage.value = null;
            }, 8000);

            // オーバーレイを閉じる
            emit("close");
        },
        onError: (errors) => {
            if (errors.icon) {
                localErrorMessage.value = errors.icon;
            } else {
                localErrorMessage.value =
                    "プロフィール画像の更新に失敗しました";
            }
        },
    });
};
</script>

<template>
    <div
        class="fixed inset-0 flex justify-center items-center z-50 bg-black/50 dark:bg-black/70"
    >
        <div class="w-80 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg relative">
            <!-- 成功メッセージ -->
            <div
                v-if="localSuccessMessage"
                class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 p-3 mb-6 rounded"
            >
                {{ localSuccessMessage }}
            </div>
            <!-- エラーメッセージ -->
            <div
                v-if="localErrorMessage"
                class="bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 p-3 mb-6 rounded"
            >
                {{ localErrorMessage }}
            </div>

            <h1 class="text-2xl font-bold mb-6 text-center text-gray-900 dark:text-gray-100">
                プロフィール画像編集
            </h1>
            <div class="flex justify-center mb-4">
                <img
                    :src="previewUrl"
                    alt="ユーザーのプロフィール写真"
                    class="w-24 h-24 rounded-full object-cover"
                />
            </div>
            <form @submit.prevent="submit" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block font-bold mb-2 text-gray-900 dark:text-gray-100" for="icon"
                        >画像を選択</label
                    >
                    <input
                        type="file"
                        id="icon"
                        @change="onImageChange"
                        class="w-full border border-gray-300 dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                    />
                </div>
                <div class="flex justify-between">
                    <button
                        type="submit"
                        class="w-32 px-4 py-2 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-md transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white text-center"
                    >
                        更新
                    </button>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-32 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition hover:bg-gray-500 dark:hover:bg-gray-500 hover:text-white text-center"
                    >
                        キャンセル
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
