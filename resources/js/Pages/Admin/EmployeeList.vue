<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { deleteItem } from "@/Utils/deleteItem";

const { props } = usePage();
const users = props.users;
const units = props.units;

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);

const openUserProfile = (user) => {
    selectedUser.value = user;
    isUserProfileVisible.value = true; // ユーザーの詳細ページを表示
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

// deleteUser関数を定義
const deleteUser = (user) => {
    deleteItem("user", user.id, (deletedUserId) => {
        // 削除したユーザーをローカルの `users` 配列から除去
        const index = users.findIndex((u) => u.id === deletedUserId);
        if (index !== -1) {
            users.splice(index, 1);
        }
    });
};
</script>

<template>
    <Head :title="$t('Employee List')" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 mt-16">
                {{ $t("Employee List") }}
            </h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white overflow-hidden shadow rounded-lg p-4 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-4">
                        <!-- プロフィール画像 -->
                        <img
                            :src="
                                user.icon
                                    ? `/storage/${user.icon}`
                                    : 'https://via.placeholder.com/150'
                            "
                            alt="Profile Icon"
                            class="w-16 h-16 rounded-full cursor-pointer link-hover"
                            @click="openUserProfile(user)"
                        />

                        <!-- ユーザー名 -->
                        <p class="text-lg font-bold text-gray-900">
                            <span
                                @click="openUserProfile(user)"
                                class="hover:underline p-1 rounded cursor-pointer"
                            >
                                {{ user.name }}
                            </span>
                        </p>
                    </div>

                    <!-- 削除ボタン -->
                    <button
                        @click="deleteUser(user)"
                        class="text-red-500 hover:text-red-700"
                    >
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <p v-if="users.length === 0" class="text-gray-500 mt-4">
            {{ $t("No user available") }}
        </p>

        <!-- 選択された投稿のユーザーの詳細ページを表示 -->
        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show v-if="selectedUser" :user="selectedUser" :units="units" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
