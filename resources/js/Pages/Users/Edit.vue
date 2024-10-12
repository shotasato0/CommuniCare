<script>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";

export default {
    props: {
        user: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        // useFormを使って編集用のフォームデータを定義
        const form = useForm({
            name: props.user.name,
            tel: props.user.tel,
            email: props.user.email,
            unit_id: props.user.unit_id,
        });

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
            form.put(`/users/${props.user.id}`);
        };

        // setup関数から必要なデータを返す
        return { form, submit, successMessage, page };
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

            <h1 class="text-2xl font-bold mb-6 text-center">ユーザー編集</h1>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label class="block font-bold mb-2" for="name">名前</label>
                    <input
                        v-model="form.name"
                        id="name"
                        type="text"
                        class="w-full border border-gray-300 p-2 rounded"
                    />
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2" for="tel"
                        >電話番号</label
                    >
                    <input
                        v-model="form.tel"
                        id="tel"
                        type="tel"
                        class="w-full border border-gray-300 p-2 rounded"
                    />
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2" for="email"
                        >メールアドレス</label
                    >
                    <input
                        v-model="form.email"
                        id="email"
                        type="email"
                        class="w-full border border-gray-300 p-2 rounded"
                    />
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2" for="unit_id"
                        >所属部署</label
                    >
                    <select
                        v-model="form.unit_id"
                        id="unit_id"
                        class="w-full border border-gray-300 p-2 rounded"
                    >
                        <option
                            v-for="unit in page.props.units"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.name }}
                        </option>
                    </select>
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
