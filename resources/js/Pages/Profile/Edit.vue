<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import IconEditForm from "./Partials/IconEditForm.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

// アイコン編集オーバーレイの表示制御
const isIconEditVisible = ref(false);
const user = usePage().props.auth.user;
const units = usePage().props.units;
const successMessage = ref(usePage().props.flash.success || null); // refに変更

// ユーザーがゲストかどうかを判定
const isGuest = computed(() => user.guest_session_id !== null);

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
        }, 8000);
    }
});
</script>

<template>
    <Head :title="$t('Profile')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Profile") }}
            </h2>
        </template>

        <!-- ゲスト向け説明文 -->
        <div
            v-if="isGuest"
            class="p-4 mb-6 bg-yellow-100 text-yellow-800 rounded"
        >
            現在、ゲストユーザーとしてログインしています。一部の機能（パスワード変更、アカウント削除など）は利用できません。
            通常のアカウントでログインすることで、これらの機能をご利用いただけます。
        </div>

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
                <div
                    v-if="!isGuest"
                    class="p-4 sm:p-8 bg-white shadow sm:rounded-lg"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- ユーザー削除フォーム -->
                <div
                    v-if="!isGuest"
                    class="p-4 sm:p-8 bg-white shadow sm:rounded-lg"
                >
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
