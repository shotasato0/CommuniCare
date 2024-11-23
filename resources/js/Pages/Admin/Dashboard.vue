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
            <!-- 社員新規登録 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Employee Registration") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Register a new employee.") }}
                    </p>
                    <div class="mt-4">
                        <Link
                            :href="route('register')"
                            class="text-blue-500 link-hover"
                            >{{ $t("Registration page") }}</Link
                        >
                    </div>
                </div>
            </div>

            <!-- 社員一覧 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Employee List") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Check the list of registered employees.") }}
                    </p>
                    <div class="mt-4">
                        <Link
                            :href="route('users.index')"
                            class="text-blue-500 link-hover"
                            >{{ $t("List page") }}</Link
                        >
                    </div>
                </div>
            </div>

            <!-- 部署管理 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Unit Management") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Manage unit names.") }}
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

            <!-- 管理者権限の譲渡 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Transfer Admin Role") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Transfer admin privileges to another user.") }}
                    </p>
                    <div class="mt-4">
                        <Link
                            :href="route('admin.transfer.view')"
                            class="text-blue-500 link-hover"
                            >{{ $t("Transfer page") }}</Link
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
