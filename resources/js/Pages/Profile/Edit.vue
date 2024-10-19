<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import IconEditForm from "./Partials/IconEditForm.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, watch } from "vue";

// アイコン編集オーバーレイの表示制御
const isIconEditVisible = ref(false);
const user = usePage().props.auth.user;
const units = usePage().props.units;
const successMessage = ref(usePage().props.flash.success || null); // refに変更

const openIconEdit = () => {
    isIconEditVisible.value = true;
};

const closeIconEdit = () => {
    isIconEditVisible.value = false;
};

// アイコン更新の反映
const handleUpdateIcon = (newIconUrl) => {
    user.icon = newIconUrl; // 新しいアイコンURLを更新
};

watch(successMessage, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            successMessage.value = null;
        }, 3000);
    }
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Profile") }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- プロフィール情報 -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm
                        class="max-w-xl"
                        :user="user"
                        :units="units"
                        :successMessage="successMessage"
                        @openIconEdit="openIconEdit"
                    />
                </div>

                <!-- パスワード変更 -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- ユーザー削除フォーム -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>

        <!-- アイコン編集オーバーレイ -->
        <div
            v-if="isIconEditVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
        >
            <IconEditForm
                :user="user"
                @close="closeIconEdit"
                @successMessage="successMessage = $event"
                @updateIcon="handleUpdateIcon"
            />
        </div>
    </AuthenticatedLayout>
</template>
