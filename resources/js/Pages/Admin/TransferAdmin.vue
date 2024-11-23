<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Show from "@/Pages/Users/Show.vue";

const { props } = usePage();
const users = props.users;
const currentAdminId = props.currentAdminId;

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);
const flashMessages = ref([]);

// ユーザープロファイル開閉
const openUserProfile = (user) => {
    selectedUser.value = user;
    isUserProfileVisible.value = true;
};
const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

// フラッシュメッセージ管理
const addFlashMessage = (message) => {
    flashMessages.value.push(message);
    setTimeout(() => {
        flashMessages.value.shift();
    }, 3000);
};

// 管理者権限の譲渡
const transferAdmin = async (user) => {
    if (confirm(`${user.name} に管理者権限を移動しますか？`)) {
        try {
            await router.post(
                route("admin.transferAdmin"),
                { new_admin_id: user.id },
                {
                    onSuccess: () => {
                        addFlashMessage(
                            `${user.name} に管理者権限を移動しました。`
                        );
                    },
                    onError: (errors) => {
                        const errorMessage =
                            errors.message ||
                            "管理者権限の移動に失敗しました。";
                        console.error("エラー:", errorMessage);
                        addFlashMessage(errorMessage);
                    },
                }
            );
        } catch (error) {
            console.error("予期しないエラー:", error);
            addFlashMessage("管理者権限の移動中に問題が発生しました。");
        }
    }
};

// 管理者とその他ユーザーの分類
const sortedUsers = computed(() => {
    const currentAdmin = users.find((user) => user.id === currentAdminId);
    const otherUsers = users.filter((user) => user.id !== currentAdminId);
    return { currentAdmin, otherUsers };
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <!-- タイトル -->
            <h1 class="text-2xl font-bold mb-6 mt-16">
                {{ $t("Transfer Admin Role") }}
            </h1>
            <p class="text-gray-700 mb-6">
                {{ $t("Select a user to transfer admin privileges.") }}
            </p>

            <!-- フラッシュメッセージ -->
            <div v-for="(message, index) in flashMessages" :key="index">
                <div
                    class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 text-green-700 p-4 rounded shadow-lg text-center"
                >
                    {{ message }}
                </div>
            </div>

            <!-- 現在の管理者 -->
            <div v-if="sortedUsers.currentAdmin" class="mb-8">
                <div
                    class="bg-white w-11/12 mx-auto sm:w-full overflow-hidden shadow rounded-lg p-3 flex items-center justify-between border-l-4 border-blue-500"
                >
                    <div
                        class="flex items-center space-x-4 cursor-pointer"
                        @click="openUserProfile(sortedUsers.currentAdmin)"
                    >
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
                    </div>
                    <p class="text-xs sm:text-sm text-blue-500 mr-4">
                        {{ $t("Current Admin") }}
                    </p>
                </div>
            </div>

            <!-- 他のユーザー -->
            <div
                v-if="sortedUsers.otherUsers.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <div
                    v-for="user in sortedUsers.otherUsers"
                    :key="user.id"
                    class="bg-white w-11/12 mx-auto sm:w-full overflow-hidden shadow rounded-lg p-3 flex items-center justify-between"
                >
                    <div
                        class="flex items-center space-x-4 cursor-pointer"
                        @click="openUserProfile(user)"
                    >
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
                    <button
                        v-if="user.id !== currentAdminId"
                        @click="transferAdmin(user)"
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
                    >
                        {{ $t("Transfer") }}
                    </button>
                </div>
            </div>
            <p v-else class="text-gray-500 mt-4">
                {{ $t("No user available") }}
            </p>
        </div>

        <!-- プロファイルポップアップ -->
        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show
                    v-if="selectedUser"
                    :user="selectedUser"
                    :units="props.units"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
