<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import AdminDashboard from "@/Pages/Admin/Dashboard.vue";

const { props } = usePage();
const pageProps = usePage().props;
const isAdmin = props.isAdmin;
const flash = props.flash;

// フラッシュメッセージ用の変数を定義
const flashMessage = ref(flash.success || flash.error || flash.info || null);
const flashType = ref(
    flash.success ? "success" : flash.error ? "error" : "info"
);

onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
            flashMessage.value = null;
        }, 3000); // 3秒後にメッセージを消す
    }
});

// コンソールにユーザー情報を出力
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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        {{ $t("You're logged in") }}
                    </div>
                </div>
            </div>
        </div>

        <!-- フラッシュメッセージの表示 -->
        <div
            v-if="flashMessage"
            :class="{
                'bg-green-100 border-l-4 border-green-500 text-green-700 p-6':
                    flashType === 'success',
                'bg-red-100 border-l-4 border-red-500 text-red-700 p-6':
                    flashType === 'error',
                'bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-6':
                    flashType === 'info',
            }"
            class="mt-4 mb-6 max-w-7xl mx-auto sm:px-6 lg:px-8 sm:rounded-lg"
        >
            <p class="font-bold">{{ flashMessage }}</p>
        </div>

        <!-- 管理者ページ -->
        <div v-if="isAdmin">
            <AdminDashboard />
        </div>
    </AuthenticatedLayout>
</template>
