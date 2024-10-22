<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

// 検索クエリの状態を保持
const search = ref("");
const isComposing = ref(false); // 変換中かどうかを管理するフラグ

// 検索実行
const searchPosts = () => {
    if (!isComposing.value) {
        // 変換中でない場合のみ検索実行
        router.get(
            route("forum.index"),
            { search: search.value },
            {
                preserveScroll: true, // ページのスクロール位置を保持
                replace: true, // ページの履歴を置き換え
            }
        );
    }
};

// 変換開始
const compositionStart = () => {
    isComposing.value = true;
};

// 変換確定
const compositionEnd = () => {
    isComposing.value = false; // 変換が終了したら検索を許可
};
</script>

<template>
    <div class="relative">
        <!-- 検索ボックス -->
        <input
            v-model="search"
            type="text"
            placeholder="投稿を検索"
            class="border p-2 w-full pr-12 rounded-full shadow-sm focus:ring-2 focus:ring-blue-500"
            @keydown.enter="searchPosts"
            @compositionstart="compositionStart"
            @compositionend="compositionEnd"
        />

        <!-- 検索アイコン -->
        <i
            class="bi bi-search absolute top-1/2 right-4 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"
            @click="searchPosts"
        ></i>
    </div>
</template>
