<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const pageProps = usePage().props;
// 検索クエリの状態を保持
const search = ref(pageProps.search || "");
const selectedUnitId = ref(pageProps.selectedunit_id || null); // ユニットIDを保持
const selectedForumId = ref(pageProps.selectedForumId || null); // 掲示板IDを保持
const isComposing = ref(false); // 変換中かどうかを管理するフラグ

// 検索実行
const searchPosts = () => {
    if (!isComposing.value) {
        // 変換中でない場合のみ検索を実行
        router.get(
            route("forum.index"),
            { search: search.value, forum_id: selectedForumId.value }, // forum_idをリクエストに含める
            {
                preserveScroll: true, // ページのスクロール位置を保持
                replace: true, // ページの履歴を置き換え
            }
        );
    }
};

// 検索リセット
const resetSearch = () => {
    search.value = "";
    router.get(
        route("forum.index"),
        { selectedunit_id: selectedUnitId.value, forum_id: selectedForumId.value },
        { replace: true }
    ); // ユニットIDと掲示板IDを保持したまま検索リセット
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
            class="border-gray-300 p-2 w-full pr-12 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500"
            @keydown.enter="searchPosts"
            @compositionstart="compositionStart"
            @compositionend="compositionEnd"
        />

        <!-- 検索アイコン -->
        <i
            class="bi bi-search absolute top-1/2 right-4 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"
            @click="searchPosts"
        ></i>
        <!-- リセットアイコン（×ボタン） -->
        <i
            v-if="search"
            class="bi bi-x absolute top-1/2 right-12 transform -translate-y-1/2 cursor-pointer text-gray-500 hover:text-gray-700"
            @click="resetSearch"
        ></i>
    </div>
</template>
