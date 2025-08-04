<template>
    <div
        v-if="isVisible"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 dark:bg-black dark:bg-opacity-70"
        style="z-index: 1000"
    >
        <div class="bg-white dark:bg-gray-800 p-6 rounded-md shadow-lg">
            <p class="text-gray-900 dark:text-gray-100">{{ message }}</p>
            <div class="flex justify-center space-x-2 mt-4">
                <button
                    v-if="!showProfileLink"
                    @click="confirm"
                    class="w-32 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 font-medium py-2 px-4 rounded-md transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    OK
                </button>
                <!-- アカウント情報ページへのボタン（特定のページのみ表示） -->
                <button
                    v-if="showProfileLink"
                    @click="goToProfile"
                    class="w-40 bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-300 font-medium py-2 px-4 rounded-md transition hover:bg-green-300 dark:hover:bg-green-600 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    アカウント情報へ
                </button>
                <button
                    @click="cancel"
                    class="w-32 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium py-2 px-4 rounded-md transition hover:bg-gray-500 dark:hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    キャンセル
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    isVisible: Boolean,
    message: String,
    showProfileLink: Boolean,
});

const emit = defineEmits(["confirm", "cancel"]);

const confirm = () => {
    emit("confirm");
};

const cancel = () => {
    emit("cancel");
};

const goToProfile = () => {
    // アカウント情報ページへのナビゲーション
    router.get(route("profile.edit"));
};
</script>
