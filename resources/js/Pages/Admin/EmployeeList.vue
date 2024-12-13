<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { ref, watchEffect } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { deleteItem } from "@/Utils/deleteItem";

const { props } = usePage();
const users = ref(props.users);
const units = props.units;
const flashMessage = ref(props.flash.success || null);
const showDeleteButtons = ref(false);

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);

const openUserProfile = (user) => {
    if (!showDeleteButtons.value) {
        selectedUser.value = user;
        isUserProfileVisible.value = true;
    }
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

const deleteUser = (user) => {
    deleteItem("user", user.id, (deletedUserId) => {
        const index = users.value.findIndex((u) => u.id === deletedUserId);
        if (index !== -1) {
            users.value.splice(index, 1);
        }
        // 削除成功時にフラッシュメッセージを設定
        flashMessage.value = "社員が削除されました。";
        showDeleteButtons.value = false;
    });
};

// flashMessageの変更を監視して、8秒後にフラッシュメッセージをクリア
watchEffect(() => {
    if (flashMessage.value) {
        const timeout = setTimeout(() => {
            flashMessage.value = null;
        }, 8000);
        // クリーンアップでタイムアウトをクリア
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
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- コントロール部分 -->
                        <div class="flex justify-end mb-6">
                            <button
                                @click="showDeleteButtons = !showDeleteButtons"
                                class="px-4 py-2 rounded-md transition delete-mode-button"
                                :class="
                                    showDeleteButtons
                                        ? 'bg-red-100 text-red-700 hover:bg-red-300 hover:text-white'
                                        : 'bg-red-200 text-red-600 hover:bg-red-400 hover:text-white'
                                "
                            >
                                削除モード
                            </button>
                        </div>

                        <!-- 社員一覧 -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="user in users"
                                :key="user.id"
                                :class="[
                                    'relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group',
                                    showDeleteButtons
                                        ? 'hover:bg-red-50 cursor-pointer'
                                        : 'hover:bg-gray-50 hover:shadow-md',
                                ]"
                                @click="
                                    showDeleteButtons
                                        ? deleteUser(user)
                                        : openUserProfile(user)
                                "
                            >
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="
                                            user.icon
                                                ? `/storage/${user.icon}`
                                                : 'https://via.placeholder.com/150'
                                        "
                                        alt="Profile Icon"
                                        class="w-12 h-12 rounded-full"
                                    />
                                    <div
                                        class="flex justify-between items-start w-full"
                                    >
                                        <span
                                            :class="[
                                                'font-bold text-lg',
                                                showDeleteButtons
                                                    ? 'text-gray-500 group-hover:text-red-500'
                                                    : 'text-gray-500 group-hover:text-black',
                                            ]"
                                        >
                                            {{ user.name }}
                                        </span>
                                        <i
                                            v-if="showDeleteButtons"
                                            class="bi bi-trash text-red-500"
                                        ></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- データが存在しない場合 -->
                        <div
                            v-if="users.length === 0"
                            class="p-8 text-center text-gray-500"
                        >
                            <i class="bi bi-people text-4xl mb-2 block"></i>
                            <p class="text-lg font-medium">
                                社員が登録されていません。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- プロフィールモーダル -->
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
