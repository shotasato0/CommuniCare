<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// URLの判定
const currentUrl = window.location.href;
const guestDomain =
    import.meta.env.VITE_GUEST_TENANT_URL || "http://guestdemo.localhost";
const isGuestUrl = ref(currentUrl.includes(`${guestDomain}/login`));

const isAdminMode = ref(
    new URLSearchParams(window.location.search).has("admin")
);

// フォームデータにusername_idを使用
const form = useForm({
    username_id: "",
    password: "",
    remember: false,
    _token: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content"),
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Login')" />

        <!-- ゲストデモテナントでかつ admin パラメータがない場合はフォーム非表示 -->
        <div
            v-if="isGuestUrl && !isAdminMode"
            class="p-6 bg-yellow-100 text-yellow-800 rounded"
        >
            セッションが切れました。アプリケーションロゴをクリックして、新たなゲストユーザーとしてログインし直してください。
        </div>

        <!-- admin パラメータがあれば通常のログインフォームを表示 -->
        <form v-else @submit.prevent="submit">
            <div>
                <InputLabel for="username_id" :value="$t('Username_ID')" />
                <TextInput
                    id="username_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.username_id"
                    required
                />
                <InputError class="mt-2" :message="form.errors.username_id" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="$t('Password')" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">{{
                        $t("Remember me")
                    }}</span>
                </label>
            </div>

            <div class="mt-6 text-sm text-gray-600">
                ユーザーID、並びにパスワードを忘れた場合は、管理者にお問い合わせください。
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t("Login") }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
