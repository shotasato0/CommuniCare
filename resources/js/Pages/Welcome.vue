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
        <div class="max-w-md mx-auto text-center mb-6">
            <h1 class="text-2xl font-bold mb-2">介護施設の登録</h1>
            <p class="text-gray-600">
                このページでは、あなたの施設専用の管理スペースを作成できます。<br />
                登録が完了すると、施設内のスタッフや利用者データを専用の環境で管理できるようになります。
            </p>
            <p class="text-sm text-gray-500 mt-2">
                以下の情報を入力し、「登録する」ボタンをクリックしてください。
            </p>
        </div>

        <!-- フォーム部分 -->
        <form
            @submit.prevent="submit"
            class="max-w-md mx-auto bg-white p-6"
        >
            <div>
                <InputLabel for="facility_name" value="施設名" />

                <TextInput
                    id="facility_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.facility_name"
                    required
                    autofocus
                />

                <InputError class="mt-2" :message="form.errors.facility_name" />
            </div>
            <div class="mt-4">
                <InputLabel for="facility_domain_id" value="施設ドメインID" />

                <TextInput
                    id="facility_domain_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.facility_domain_id"
                    required
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.facility_domain_id"
                />
            </div>

            <!-- ヒントや補足 -->
            <div class="text-sm text-gray-500 mt-4">
                <p>
                    施設ドメインIDは、あなたの施設を一意に識別するために使用されます。アルファベットのみで入力してください。
                </p>
                <p class="mt-1">
                    例：「myfacility」と入力すると、「myfacility.com」のように使用されます。
                </p>
            </div>

            <div class="flex items-center justify-end mt-6">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    登録する
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
