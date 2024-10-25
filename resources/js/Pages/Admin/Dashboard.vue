<script setup>
import { Link, Head } from "@inertiajs/vue3";
</script>

<template>
    <Head :title="$t('Admin Dashboard')" />
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
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
                            class="text-indigo-600 hover:text-indigo-900"
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
                            class="text-indigo-600 hover:text-indigo-900"
                            >{{ $t("List page") }}</Link
                        >
                    </div>
                </div>
            </div>

            <!-- ユニット名登録 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ $t("Unit Name Registration") }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $t("Register a new unit name.") }}
                    </p>
                    <div class="mt-4">
                        <Link
                            :href="route('units.create')"
                            class="text-indigo-600 hover:text-indigo-900"
                            >{{ $t("Registration page") }}</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
