<script>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch, ref } from "vue";

export default {
    props: {
        user: {
            type: Object,
            required: true,
        },
    },
    emits: ["updateIcon", "close"], // updateIconイベントを追加
    setup(props, { emit }) {
        // アイコン編集用のフォームデータを定義
        const form = useForm({
            icon: null, // アイコンを追加
        });

        // 選択された画像のプレビューURLを保存するref
        const previewUrl = ref(
            props.user.icon
                ? `/storage/${props.user.icon}`
                : "https://via.placeholder.com/100"
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
                    "image/svg+xml",
                    "image/webp",
                ];
                if (!validImageTypes.includes(file.type)) {
                    localErrorMessage.value =
                        "対応していないファイル形式です。png, jpg, gif, svg, webpのいずれかを選択してください。";

                    // 3秒後にエラーメッセージを自動的に消す処理
                    setTimeout(() => {
                        localErrorMessage.value = null;
                    }, 5000);

                    return;
                }

                form.icon = file;
                previewUrl.value = URL.createObjectURL(file);
            }
        };

        // usePageからページのプロパティを取得
        const page = usePage();

        // 成功メッセージをcomputedで取得
        const successMessage = computed(() => {
            return page.props.flash && page.props.flash.success
                ? page.props.flash.success
                : null;
        });

        // エラーメッセージをcomputedで取得
        const errorMessage = computed(() => {
            return page.props.errors && page.props.errors.icon
                ? page.props.errors.icon
                : null;
        });

        // ローカルの成功メッセージをrefで定義
        const localSuccessMessage = ref(successMessage.value);

        // ローカルの成功メッセージをrefで定義
        watch(successMessage, (newValue) => {
            if (newValue) {
                localSuccessMessage.value = newValue;
                setTimeout(() => {
                    localSuccessMessage.value = null;
                }, 5000);
            }
        });

        // ローカルのエラーメッセージをrefで定義
        const localErrorMessage = ref(errorMessage.value);

        // ローカルのエラーメッセージをrefで定義
        watch(errorMessage, (newValue) => {
            if (newValue) {
                localErrorMessage.value = newValue;
                setTimeout(() => {
                    localErrorMessage.value = null;
                }, 5000);
            }
        });

        // フォーム送信処理
        const submit = () => {
            form.post(`/users/${props.user.id}/update-icon`, {
                forceFormData: true, // ファイルアップロードを有効にするために必要
                onSuccess: () => {
                    console.log("アイコン更新成功");
                    if (form.icon) {
                        previewUrl.value = URL.createObjectURL(form.icon);
                    }
                    // 親コンポーネントにアイコンが更新されたことを通知
                    emit("updateIcon", previewUrl.value);
                    // オーバーレイを閉じる
                    emit("close");
                },
                onError: (errors) => {
                    console.log("アイコン更新エラー", errors);
                    if (errors.icon) {
                        localErrorMessage.value = errors.icon[0];
                    }
                },
            });
        };

        // setup関数から必要なデータを返す
        return {
            form,
            submit,
            localSuccessMessage,
            localErrorMessage,
            page,
            previewUrl,
            handleImageChange,
        };
    },
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
