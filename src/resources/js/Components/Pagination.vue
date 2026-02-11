<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1 justify-center">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 dark:text-gray-500 border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800"
                    v-html="link.label"
                />
                <Link
                    v-else
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border border-gray-300 dark:border-gray-600 rounded hover:bg-white dark:hover:bg-gray-700 focus:border-indigo-500 dark:focus:border-indigo-400 focus:text-indigo-500 dark:focus:text-indigo-400 text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800"
                    :class="{ 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400': link.active }"
                    :href="link.url"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
