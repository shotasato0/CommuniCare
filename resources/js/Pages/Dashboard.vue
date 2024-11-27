<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import AdminDashboard from "@/Pages/Admin/Dashboard.vue";

const { props } = usePage();
const isAdmin = props.isAdmin;
const flash = props.flash;
const isGuest = props.isGuest;

const flashMessage = ref(flash.success || flash.error || flash.info || null);
const flashType = ref(
    flash.success ? "success" : flash.error ? "error" : "info"
);

// 3秒後にフラッシュメッセージをフェードアウトさせる
const showFlashMessage = ref(true);
onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
            showFlashMessage.value = false;
        }, 3000);
    }
});

console.log("User data:", props.auth.user);
</script>

<template>
    <Head :title="$t('Dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Dashboard") }}
            </h2>
        </template>

        <!-- ダッシュボードのコンテンツ全体に左右余白を追加 -->
        <div class="py-12 px-4 sm:px-8 lg:px-16">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="!isGuest" class="p-6 text-gray-900">
                        {{ $t("You're logged in") }}
                    </div>
                    <div v-else class="p-6 text-gray-900">
                        {{ $t("Logged in as guest user") }}
                    </div>
                </div>
            </div>
        </div>

        <!-- フラッシュメッセージの表示 -->
        <transition name="fade">
            <div
                v-if="flashMessage && showFlashMessage"
                :class="{
                    'bg-green-100 border-l-4 border-green-500 text-green-700 p-4':
                        flashType === 'success',
                    'bg-red-100 border-l-4 border-red-500 text-red-700 p-4':
                        flashType === 'error',
                    'bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4':
                        flashType === 'info',
                }"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md mx-auto sm:rounded-lg shadow-lg"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <!-- 管理者ページ -->
        <div v-if="isAdmin">
            <AdminDashboard />
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
