<template>
    <Head :title="$t('Transfer Admin Role')" />

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
            <h1 class="text-2xl font-bold mb-6">
                {{ $t("Transfer Admin Role") }}
            </h1>
            <p class="text-gray-700 mb-6">
                {{ $t("Select a user to transfer admin privileges.") }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="user in users"
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
                    <button
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

<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";

// フラッシュメッセージ
const flashMessage = ref(null);

// ダミーデータとしてのユーザーリスト
const users = [
    // ここにAPIレスポンスなどで取得したユーザーリストを格納
    { id: 1, name: "山田 太郎", icon: null },
    { id: 2, name: "佐藤 花子", icon: null },
];

// 管理者権限の譲渡処理
const transferAdmin = (user) => {
    if (confirm(`${user.name} に管理者権限を移動しますか？`)) {
        // APIリクエストを送信
        $inertia
            .post(route("admin.transferAdmin"), { user_id: user.id })
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
