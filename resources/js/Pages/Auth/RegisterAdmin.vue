<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { ref } from "vue";

// CSRFトークンを取得
const csrfToken = ref(
    document.querySelector('meta[name="csrf-token"]').getAttribute("content")
);

// フォームの初期データを定義
const form = useForm({
    name: "",
    username_id: "",
    password: "",
    password_confirmation: "",
    _token: csrfToken.value,
});

const submit = () => {
    form.post(route("register-admin"), {
        onFinish: () => {
            form.reset("password", "password_confirmation");
        },
        onError: (errors) => {
            console.error("Form submission errors:", errors);
        },
        headers: {
            "X-CSRF-TOKEN": csrfToken.value,
        },
    });
};
</script>

<template>
    <!-- 独自のレイアウトを適用 -->
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
        <Head :title="$t('Admin Registration')" />

        <!-- フォームコンテナ -->
        <div class="max-w-md w-full bg-white shadow-md rounded px-8 py-10">
            <h1 class="text-2xl font-bold text-center mb-6">
                {{ $t("Admin Registration") }}
            </h1>

            <!-- 登録フォーム -->
            <form @submit.prevent="submit" class="space-y-6">
                <input type="hidden" name="_token" :value="csrfToken" />

                <!-- 名前フィールド -->
                <div>
                    <InputLabel for="name" :value="$t('Name')" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- ユーザー名フィールド -->
                <div>
                    <InputLabel for="username_id" :value="$t('Username_ID')" />
                    <TextInput
                        id="username_id"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.username_id"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.username_id"
                    />
                </div>

                <!-- パスワードフィールド -->
                <div>
                    <InputLabel for="password" :value="$t('Password')" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <!-- パスワード確認フィールド -->
                <div>
                    <InputLabel
                        for="password_confirmation"
                        :value="$t('Confirm Password')"
                    />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>

                <!-- 登録ボタン -->
                <div class="flex items-center justify-end">
                    <button
                        type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ $t("Register") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
