<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
});

const submit = () => {
    form.post(route("units.store"));
};
</script>

<template>
    <Head :title="$t('Unit Registration')" />
    <div class="max-w-2xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">{{ $t("Unit Registration") }}</h1>

        <form @submit.prevent="submit" class="bg-white p-6 rounded-lg shadow">
            <div class="mb-4">
                <label
                    for="name"
                    class="block text-sm font-medium text-gray-700"
                    >{{ $t("Unit Name") }}</label
                >
                <input
                    type="text"
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">
                    {{ form.errors.name }}
                </div>
            </div>

            <button
                type="submit"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            >
                {{ $t("Register Unit") }}
            </button>
        </form>
    </div>
</template>
