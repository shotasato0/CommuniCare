<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    user: Object,
});

const emit = defineEmits(["close", "updateIcon", "successMessage"]);

// アイコン編集用のフォームデータを定義
const form = useForm({
    icon: null, // アイコンを追加
});

// アイコンのプレビューURLを定義
const previewUrl = ref(
    props.user.icon && typeof props.user.icon === 'string' && props.user.icon.startsWith("/storage/")
        ? props.user.icon
        : props.user.icon
        ? `/storage/${props.user.icon}`
        : "https://via.placeholder.com/100"
);


// ローカルプレビュー用の一時的なBlob URLかどうかを識別するフラグ
const isLocalPreview = ref(false);

// 成功メッセージとエラーメッセージのrefを定義
const localSuccessMessage = ref(null); // 初期値をnullに設定
const localErrorMessage = ref(null); // 初期値をnullに設定

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

            // 5秒後にエラーメッセージを自動的に消す処理
            setTimeout(() => {
                localErrorMessage.value = null;
            }, 5000);

            return;
        }

        form.icon = file;
        previewUrl.value = URL.createObjectURL(file); // ローカルプレビュー用にBlob URLをセット
        isLocalPreview.value = true; // ローカルプレビュー用のフラグを立てる
    }
};

// フォーム送信処理
const submit = () => {
    form.post(route("profile.updateIcon"), {
        forceFormData: true,
        onSuccess: () => {
            console.log("アイコン更新成功");

            // サーバーから新しいアイコンパスが返される場合は、その値を使う
            const updatedIcon = usePage().props.auth.user.icon;

            if (updatedIcon) {
                // 正しいパスを構築する
                previewUrl.value = `/storage/${updatedIcon}`;
                emit("updateIcon", previewUrl.value);
                emit("successMessage", "プロフィール画像が更新されました");
            } else {
                // パスがない場合はプレースホルダーに戻す
                previewUrl.value = "https://via.placeholder.com/100";
            }

            // サクセスメッセージをemitして親コンポーネントで表示させる
            emit("successMessage", "プロフィール画像が更新されました");

            // 成功メッセージを設定
            localSuccessMessage.value = "プロフィール画像が更新されました";

            // 5秒後にサクセスメッセージを自動的に消す
            setTimeout(() => {
                localSuccessMessage.value = null;
            }, 5000);

            // オーバーレイを閉じる
            emit("close");
        },
        onError: (errors) => {
            console.log("アイコン更新エラー", errors);
            localErrorMessage.value = "アイコン更新に失敗しました";
        },
    });
};
</script>

<template>
    <div
        class="fixed inset-0 flex justify-center items-center z-50 bg-black/50"
    >
        <div class="w-80 bg-white p-6 rounded-lg shadow-lg relative">
            <!-- 成功メッセージ -->
            <div
                v-if="localSuccessMessage"
                class="bg-green-100 text-green-700 p-3 mb-6 rounded"
            >
                {{ localSuccessMessage }}
            </div>
            <!-- エラーメッセージ -->
            <div
                v-if="localErrorMessage"
                class="bg-red-100 text-red-700 p-3 mb-6 rounded"
            >
                {{ localErrorMessage }}
            </div>

            <h1 class="text-2xl font-bold mb-6 text-center">
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
                    <label class="block font-bold mb-2" for="icon"
                        >画像を選択</label
                    >
                    <input
                        type="file"
                        id="icon"
                        @change="handleImageChange"
                        class="w-full border border-gray-300 p-2 rounded"
                    />
                </div>
                <div class="flex justify-between">
                    <button
                        type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    >
                        更新
                    </button>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                    >
                        キャンセル
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
