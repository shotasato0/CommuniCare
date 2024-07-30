<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

// CSRFトークンを取得
const csrfToken = ref(
    document.querySelector('meta[name="csrf-token"]').getAttribute("content")
);
console.log("CSRF Token:", csrfToken.value);

const form = useForm({
    name: "",
    username_id: "",
    password: "",
    password_confirmation: "",
    _token: csrfToken.value, // フォームデータにトークンを含める
});

const submit = () => {
    console.log("Submitting form with data:", form);
    form.post(route("register"), {
        onFinish: () => {
            console.log("Form submission finished");
            form.reset("password", "password_confirmation");
        },
        onError: (errors) => {
            console.error("Form submission errors:", errors);
        },
        headers: {
            "X-CSRF-TOKEN": csrfToken.value, // リクエストヘッダーにトークンを含める
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <input type="hidden" name="_token" :value="csrfToken.value" />

            <div>
                <InputLabel for="name" value="Name" />
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

            <div class="mt-4">
                <InputLabel for="username_id" value="Username_ID" />
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
                <InputLabel for="password" value="Password" />
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

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
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

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Already registered?
                </Link>
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
