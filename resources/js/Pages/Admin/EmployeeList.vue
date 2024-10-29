<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

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
</script>

<template>
    <Head :title="$t('Employee List')" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 mt-16">{{ $t("Employee List") }}</h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="bg-white overflow-hidden shadow rounded-lg p-4 flex items-center space-x-4"
                >
                    <img
                        :src="
                            user.icon
                                ? `/storage/${user.icon}`
                                : 'https://via.placeholder.com/150'
                        "
                        alt="Profile Icon"
                        class="w-16 h-16 rounded-full cursor-pointer hover:opacity-70"
                        @click="openUserProfile(user)"
                    />

                    <p class="text-lg font-bold text-gray-900">
                        <span
                            @click="openUserProfile(user)"
                            class="hover:bg-blue-300 p-1 rounded cursor-pointer"
                        >
                            {{ user.name }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- 選択された投稿のユーザーの詳細ページを表示 -->
        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show v-if="selectedUser" :user="selectedUser" :units="units"/>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
