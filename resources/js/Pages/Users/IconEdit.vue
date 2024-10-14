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
    setup(props) {
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

        // 画像が選択された際にプレビューURLを更新する関数
        const handleImageChange = (e) => {
            const file = e.target.files[0];
            if (file) {
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

        // フラッシュメッセージを自動的に非表示にする処理
        watch(successMessage, (newValue) => {
            if (newValue) {
                setTimeout(() => {
                    page.props.flash.success = null;
                }, 3000);
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
                },
                onError: (errors) => {
                    console.error("アイコン更新エラー", errors);
                },
            });
        };

        // setup関数から必要なデータを返す
        return {
            form,
            submit,
            successMessage,
            page,
            previewUrl,
            handleImageChange,
        };
    },
};
</script>

<template>
    <div class="flex justify-center items-center min-h-screen bg-gray-100">
        <div class="w-80 bg-white p-6 rounded-lg shadow-lg">
            <!-- 成功メッセージ -->
            <div
                v-if="successMessage"
                class="bg-green-100 text-green-700 p-3 mb-6 rounded"
            >
                {{ successMessage }}
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
                        >プロフィール画像</label
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
                        @click="$inertia.visit(`/users/${user.id}`)"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400"
                    >
                        キャンセル
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
