<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
    facility_name: "",
    facility_domain_id: "",
    remember: false,
});

const submit = () => {
    form.post(route("facility.register"), {
        onFinish: () => form.reset("facility_name", "facility_domain_id"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="'施設登録'" />

        <!-- 説明文 -->
        <div class="max-w-md mx-auto text-center ">
            <h1 class="text-2xl font-bold mb-4">介護施設の登録</h1>

            <p class="text-gray-600 leading-relaxed">
                あなたの施設専用の管理スペースを作成できます。登録が完了すると、スタッフや利用者データを安全に管理できる専用の環境をご利用いただけます。
            </p>

            <p class="text-sm text-gray-500 mt-6 leading-relaxed">
                以下の情報を入力し、「登録する」ボタンをクリックしてください。
            </p>
        </div>

        <!-- フォーム部分 -->
        <form
            @submit.prevent="submit"
            class="max-w-md mx-auto bg-white p-8"
        >
            <div class="mb-6">
                <InputLabel for="facility_name" value="施設名" />

                <TextInput
                    id="facility_name"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.facility_name"
                    required
                    autofocus
                />

                <InputError class="mt-2" :message="form.errors.facility_name" />
            </div>

            <div class="mb-6">
                <InputLabel for="facility_domain_id" value="施設ドメインID" />

                <TextInput
                    id="facility_domain_id"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.facility_domain_id"
                    required
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.facility_domain_id"
                />
            </div>

            <!-- ヒントや補足 -->
            <div class="text-sm text-gray-500 mb-6 leading-relaxed">
                <p>
                    <strong>施設ドメインID</strong>
                    は、施設を一意に識別するために使用されます。英字のみで入力してください。
                </p>
                <p class="mt-3">
                    例：「example」と入力すると、次のようなURLでアクセスできます：
                    <strong>https://example.communicare.com</strong>
                </p>
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    class="ms-4 px-6 py-2"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    登録する
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
