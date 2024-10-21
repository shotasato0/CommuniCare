<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// 検索クエリの状態を保持
const search = ref("");

// 検索実行
const searchPosts = () => {
    router.get(
        route("forum.index"),
        { search: search.value },
        { preserveState: true }
    );
};
</script>

<template>
    <div class="relative">
        <input
            v-model="search"
            type="text"
            placeholder="タイトルやメッセージを検索"
            class="border p-2 w-full pr-12 rounded-full shadow-sm focus:ring-2 focus:ring-blue-500"
        />
        <!-- 検索アイコン -->
        <i
            class="bi bi-search absolute top-1/2 right-4 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"
            @click="searchPosts"
        ></i>
    </div>
</template>
