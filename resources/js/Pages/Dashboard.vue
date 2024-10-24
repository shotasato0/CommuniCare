<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { onMounted } from "vue";
import AdminDashboard from "@/Pages/Admin/Dashboard.vue";

const { props } = usePage();
const pageProps = usePage().props;
const isAdmin = props.isAdmin;

onMounted(() => {
    // Flashメッセージからリロードフラグを確認
    if (props.flash && props.flash.reload_page) {
        console.log("リロードフラグがtrueです。ページをリロードします。");
        window.location.reload(); // リロードをトリガー
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
        <div v-if="isAdmin">
            <AdminDashboard />
        </div>
    </AuthenticatedLayout>
</template>
