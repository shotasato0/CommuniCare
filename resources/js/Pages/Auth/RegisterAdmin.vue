<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { ref } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";

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

// フォームの送信
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
    <GuestLayout>
        <Head :title="$t('Admin Registration')" />

        <!-- フォームコンテナ -->
        <div class="w-full sm:max-w-md px-6 py-4 mx-auto mt-6">
            <h1 class="text-xl font-bold text-center mb-6">
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

                <!-- ユーザーIDフィールド -->
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
                        class="bg-blue-100 text-blue-700 font-medium py-2 px-4 rounded-md transition hover:bg-blue-300 hover:text-white focus:outline-none focus:shadow-outline"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ $t("Register") }}
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
