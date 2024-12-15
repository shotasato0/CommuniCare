<script setup>
import { Link, Head } from "@inertiajs/vue3";
</script>

<template>
    <Head :title="$t('Admin Dashboard')" />
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-8 lg:px-16">
        <h1 class="text-2xl font-bold mb-6">
            {{ $t("Admin Dashboard") }}
        </h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 部署管理 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Unit Management") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Manage unit.") }}
                    </p>
                    <div class="mt-4">
                        <Link
                            :href="route('units.create')"
                            class="text-blue-500 link-hover"
                            >{{ $t("Management page") }}</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.link-hover:hover {
    opacity: 0.7;
}
</style>
