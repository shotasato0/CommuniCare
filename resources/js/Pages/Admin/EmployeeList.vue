<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { ref, watchEffect } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { deleteItem } from "@/Utils/deleteItem";

const { props } = usePage();
const users = props.users;
const units = props.units;
const flashMessage = ref(props.flash.success || null);

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);

const openUserProfile = (user) => {
    selectedUser.value = user;
    isUserProfileVisible.value = true;
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

const deleteUser = (user) => {
    deleteItem("user", user.id, (deletedUserId) => {
        const index = users.findIndex((u) => u.id === deletedUserId);
        if (index !== -1) {
            users.splice(index, 1);
        }
        // 削除成功時にフラッシュメッセージを設定
        flashMessage.value = "社員が削除されました。";
    });
};

// flashMessageの変更を監視して非表示タイマーを設定
watchEffect(() => {
    if (flashMessage.value) {
        const timeout = setTimeout(() => {
            flashMessage.value = null;
        }, 3000);

        // クリーンアップでタイマーをクリア
        return () => clearTimeout(timeout);
    }
});
</script>

<template>
    <Head :title="$t('Employee List')" />

    <AuthenticatedLayout>
        <!-- フラッシュメッセージ -->
        <transition name="fade">
            <div
                v-if="flashMessage"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 text-green-700 p-4 rounded shadow-lg text-center"
            >
                {{ flashMessage }}
            </div>
        </transition>

        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 mt-16">
                {{ $t("Employee List") }}
            </h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white overflow-hidden shadow rounded-lg p-4 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-4">
                        <img
                            :src="
                                user.icon
                                    ? `/storage/${user.icon}`
                                    : 'https://via.placeholder.com/150'
                            "
                            alt="Profile Icon"
                            class="w-16 h-16 rounded-full cursor-pointer link-hover"
                            @click="openUserProfile(user)"
                        />
                        <p class="text-lg font-bold text-gray-900">
                            <span
                                @click="openUserProfile(user)"
                                class="hover:underline p-1 rounded cursor-pointer"
                            >
                                {{ user.name }}
                            </span>
                        </p>
                    </div>
                    <button
                        @click="deleteUser(user)"
                        class="text-red-500 hover:text-red-700"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <p v-if="users.length === 0" class="text-gray-500 mt-4">
            {{ $t("No user available") }}
        </p>

        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show v-if="selectedUser" :user="selectedUser" :units="units" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
    opacity: 0;
}
</style>
