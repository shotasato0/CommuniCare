<script setup>
import { Head, usePage, Link } from "@inertiajs/vue3";

const { props } = usePage();
const users = props.users;
</script>

<template>
    <Head :title="$t('Employee List')" />

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6">{{ $t("Employee List") }}</h1>

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
                    class="w-16 h-16 rounded-full"
                />

                <Link :href="route('users.show', user.id)" class="flex-grow">
                    <p class="text-lg font-bold text-gray-900">
                        {{ user.name }}
                    </p>
                </Link>
            </div>
        </div>
    </div>
</template>
