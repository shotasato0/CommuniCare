<script setup>
import { Link, Head } from "@inertiajs/vue3";
</script>

<template>
    <Head :title="$t('Admin Dashboard')" />
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">管理者ダッシュボード</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 社員新規登録 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        社員新規登録
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        新しい社員を登録します。
                    </p>
                    <div class="mt-4">
                        <Link
                            href="/admin/employees/register"
                            class="text-indigo-600 hover:text-indigo-900"
                            >登録ページへ</Link
                        >
                    </div>
                </div>
            </div>

            <!-- 社員一覧 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        社員一覧
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        登録済みの社員の一覧を確認できます。
                    </p>
                    <div class="mt-4">
                        <Link
                            href="/admin/employees/list"
                            class="text-indigo-600 hover:text-indigo-900"
                            >一覧ページへ</Link
                        >
                    </div>
                </div>
            </div>

            <!-- ユニット名登録 -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        ユニット名登録
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        新しいユニット名を登録します。
                    </p>
                    <div class="mt-4">
                        <Link
                            href="/admin/units/register"
                            class="text-indigo-600 hover:text-indigo-900"
                            >登録ページへ</Link
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
