<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
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
    form.post(route("register.post"), {
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
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("User registration") }}
            </h2>
        </template>
        <Head :title="$t('User registration')" />

        <!-- コンテンツを中央に寄せ、画面幅に応じて余白を調整 -->
        <div class="max-w-md mx-auto py-10 mt-16 px-4 sm:px-6 lg:px-8">
            <form
                @submit.prevent="submit"
                class="bg-white p-6 rounded-lg shadow space-y-6"
            >
                <input type="hidden" name="_token" :value="csrfToken" />

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

                <div class="flex items-center justify-end">
                    <button
                        type="submit"
                        class="ms-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ $t("Register") }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
