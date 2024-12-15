<script setup>
import { Head, usePage, Link, router } from "@inertiajs/vue3";
import { ref, watchEffect, computed } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { deleteItem } from "@/Utils/deleteItem";

const { props } = usePage();
const users = ref(props.users);
const currentAdminId = props.currentAdminId;
const units = props.units;
const flashMessage = ref(props.flash.success || null);
const showDeleteButtons = ref(false);

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);
const isAdminMode = ref(false);
const confirmationDialog = ref(false);
const targetUser = ref(null);

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
        flashMessage.value = "社員が削除されました";
        showDeleteButtons.value = false;
    });
};

const handleAdminTransfer = (user) => {
    if (!isAdminMode.value) return;

    targetUser.value = user;
    confirmationDialog.value = true;
};

const executeAdminTransfer = async () => {
    try {
        await router.post(
            route("admin.transferAdmin"),
            { new_admin_id: targetUser.value.id },
            {
                onSuccess: () => {
                    flashMessage.value = `${targetUser.value.name}に管理者権限を譲渡しました`;
                    confirmationDialog.value = false;
                    targetUser.value = null;
                },
                onError: (errors) => {
                    const errorMessage =
                        errors.message || "管理者権限の移動に失敗しました";
                    console.error("エラー:", errorMessage);
                    flashMessage.value = errorMessage;
                },
            }
        );
    } catch (error) {
        console.error("予期しないエラー:", error);
        flashMessage.value = "管理者権限の移動中に問題が発生しました";
    }
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

// 管理者かどうかを判定する関数
const isAdmin = (userId) => {
    return userId === currentAdminId;
};

// 管理者とその他ユーザーの分類
const sortedUsers = computed(() => {
    const currentAdmin = users.value.find((user) => user.id === currentAdminId);
    const otherUsers = users.value.filter((user) => user.id !== currentAdminId);
    return { currentAdmin, otherUsers };
});
</script>

<template>
    <Head :title="$t('Employee List')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Employee List") }}
            </h2>
        </template>
        <!-- フラッシュメッセージ -->
        <transition name="fade">
            <div
                v-if="flashMessage"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg z-50"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- コントロール部分 -->
                        <div class="flex justify-end mb-6 space-x-4">
                            <button
                                @click="isAdminMode = !isAdminMode"
                                class="px-4 py-2 rounded-md transition"
                                :class="
                                    isAdminMode
                                        ? 'bg-purple-600 text-white'
                                        : 'bg-gray-100 text-gray-700'
                                "
                            >
                                {{
                                    isAdminMode
                                        ? "管理者権限譲渡モード"
                                        : "通常モード"
                                }}
                            </button>
                            <Link
                                :href="route('register')"
                                class="px-4 py-2 rounded-md transition bg-blue-100 text-blue-700 hover:bg-blue-300 hover:text-white"
                            >
                                新規登録
                            </Link>
                            <button
                                @click="showDeleteButtons = !showDeleteButtons"
                                class="px-4 py-2 rounded-md transition"
                                :class="
                                    showDeleteButtons
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-red-200 text-red-600'
                                "
                            >
                                削除モード
                            </button>
                        </div>

                        <!-- モード説明 -->
                        <div
                            v-if="isAdminMode"
                            class="mb-4 p-4 bg-purple-100 rounded-lg"
                        >
                            <p class="text-purple-700">
                                管理者権限譲渡モード:
                                選択したユーザーに管理者権限を譲渡します。
                                この操作を行うと、現在の管理者権限は失われます。
                            </p>
                        </div>

                        <!-- 現在の管理者 -->
                        <div v-if="sortedUsers.currentAdmin" class="mb-8">
                            <div
                                class="bg-white w-11/12 mx-auto sm:w-full overflow-hidden shadow rounded-lg p-3 flex items-center justify-between border-l-4 border-blue-500 group cursor-pointer hover:bg-gray-50 transition-all"
                                @click="
                                    openUserProfile(sortedUsers.currentAdmin)
                                "
                            >
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="
                                            sortedUsers.currentAdmin.icon
                                                ? `/storage/${sortedUsers.currentAdmin.icon}`
                                                : 'https://via.placeholder.com/150'
                                        "
                                        alt="Profile Icon"
                                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full"
                                    />
                                    <div class="flex items-center">
                                        <p
                                            class="text-sm sm:text-lg font-bold text-gray-900 group-hover:text-black"
                                        >
                                            {{ sortedUsers.currentAdmin.name }}
                                        </p>
                                        <i
                                            class="bi bi-award-fill text-yellow-500 text-xl ml-2"
                                        ></i>
                                    </div>
                                </div>
                                <p
                                    class="text-xs sm:text-sm text-blue-500 mr-4"
                                >
                                    現在の管理者
                                </p>
                            </div>
                        </div>

                        <!-- ユーザー一覧 -->
                        <div
                            v-if="sortedUsers.otherUsers.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="user in sortedUsers.otherUsers"
                                :key="user.id"
                                class="relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group cursor-pointer hover:bg-gray-50"
                                @click="
                                    isAdminMode
                                        ? handleAdminTransfer(user)
                                        : showDeleteButtons
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
                                        <div class="flex items-center">
                                            <span
                                                class="font-bold text-lg group-hover:text-black"
                                            >
                                                {{ user.name }}
                                            </span>
                                        </div>
                                        <i
                                            v-if="showDeleteButtons"
                                            class="bi bi-trash text-red-500"
                                        ></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 mt-4">
                            表示できるユーザーがいません
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 確認ダイアログ -->
        <div
            v-if="confirmationDialog"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
        >
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">管理者権限の譲渡確認</h3>
                <p class="mb-4">
                    {{ targetUser?.name }}さんに管理者権限を譲渡しますか？
                    この操作を行うと、あなたの管理者権限は失われます。
                </p>
                <div class="flex justify-end space-x-4">
                    <button
                        @click="confirmationDialog = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md"
                    >
                        キャンセル
                    </button>
                    <button
                        @click="executeAdminTransfer"
                        class="px-4 py-2 bg-purple-600 text-white rounded-md"
                    >
                        譲渡する
                    </button>
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

        <!-- デバッグ表示を追加 -->
        <div class="p-4 bg-gray-100">
            <p>Current Admin ID: {{ currentAdminId }}</p>
            <p>Users: {{ users }}</p>
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
