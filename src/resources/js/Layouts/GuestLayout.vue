<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import Footer from "@/Layouts/Footer.vue";

const currentPage = usePage().component;
</script>

<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900"
    >
        <!-- ロゴ部分 -->
        <div class="w-full flex justify-center">
            <Link
                href="/"
                :class="{ 'pointer-events-none': currentPage === 'Welcome' }"
            >
                <ApplicationLogo class="w-full text-blue-900 text-center" />
            </Link>
        </div>

        <!-- コンテンツ部分 -->
        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
    <Footer />
</template>
