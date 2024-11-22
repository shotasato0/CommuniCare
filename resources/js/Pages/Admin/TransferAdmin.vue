<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const { props } = usePage();
const users = props.users;
const currentAdminId = props.currentAdminId; // 現在の管理者のIDを取得

const flashMessage = ref(null);

// 現在の管理者とそれ以外のユーザーを分ける
const sortedUsers = computed(() => {
    const currentAdmin = users.find((user) => user.id === currentAdminId);
    const otherUsers = users.filter((user) => user.id !== currentAdminId);

    return {
        currentAdmin,
        otherUsers,
    };
});

// 管理者権限の譲渡処理
const transferAdmin = (user) => {
    if (confirm(`${user.name} に管理者権限を移動しますか？`)) {
        router
            .post(route("admin.transferAdmin"), { new_admin_id: user.id })
            .then(() => {
                flashMessage.value = `${user.name} に管理者権限を移動しました。`;
                setTimeout(() => (flashMessage.value = null), 3000);
            })
            .catch((error) => {
                console.error("エラー:", error);
                flashMessage.value = "管理者権限の移動に失敗しました。";
                setTimeout(() => (flashMessage.value = null), 3000);
            });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 mt-16">
                {{ $t("Transfer Admin Role") }}
            </h1>
            <p class="text-gray-700 mb-6">
                {{ $t("Select a user to transfer admin privileges.") }}
            </p>

            <!-- 現在の管理者 -->
            <div v-if="sortedUsers.currentAdmin" class="mb-8">
                <div
                    class="bg-white w-11/12 mx-auto sm:w-full overflow-hidden shadow rounded-lg p-3 flex items-center justify-between border-l-4 border-blue-500"
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
                                class="text-sm sm:text-lg font-bold text-gray-900"
                            >
                                {{ sortedUsers.currentAdmin.name }}
                            </p>
                            <i
                                class="bi bi-award-fill text-yellow-500 text-xl ml-2"
                            ></i>
                        </div>
                        <p class="text-xs sm:text-sm text-blue-500">
                            {{ $t("Current Admin") }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 他のユーザー -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="user in sortedUsers.otherUsers"
                    :key="user.id"
                    class="bg-white w-11/12 mx-auto sm:w-full overflow-hidden shadow rounded-lg p-3 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-4">
                        <img
                            :src="
                                user.icon
                                    ? `/storage/${user.icon}`
                                    : 'https://via.placeholder.com/150'
                            "
                            alt="Profile Icon"
                            class="w-12 h-12 sm:w-16 sm:h-16 rounded-full"
                        />
                        <p class="text-sm sm:text-lg font-bold text-gray-900">
                            {{ user.name }}
                        </p>
                    </div>
                    <!-- 管理者以外にのみ移動ボタンを表示 -->
                    <button
                        v-if="user.id !== currentAdminId"
                        @click="transferAdmin(user)"
                        class="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition"
                    >
                        {{ $t("Transfer") }}
                    </button>
                </div>
            </div>

            <p v-if="users.length === 0" class="text-gray-500 mt-4">
                {{ $t("No user available") }}
            </p>
        </div>
    </AuthenticatedLayout>
</template>
