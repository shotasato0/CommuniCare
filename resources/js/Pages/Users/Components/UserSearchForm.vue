<script setup>
import { ref } from "vue";

const props = defineProps({
    units: {
        type: Array,
        required: true,
    },
    selectedUnitId: {
        type: [Number, String],
        default: "",
    },
    totalResults: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["update:selectedUnit", "update:searchQuery"]);

const selectedUnit = ref(props.selectedUnitId || "");
const searchQuery = ref("");

// 選択された部署が変更された時
const handleUnitChange = (event) => {
    emit("update:selectedUnit", event.target.value);
};

// 検索をリセットする関数
const resetSearch = () => {
    searchQuery.value = "";
    emit("update:searchQuery", "");
};
</script>

<template>
    <div
        class="flex flex-col sm:flex-row items-start space-y-4 sm:space-y-0 sm:space-x-4 "
    >
        <!-- 部署選択プルダウン -->
        <div class="w-full sm:w-auto">
            <select
                v-model="selectedUnit"
                @change="handleUnitChange"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
            >
                <option value="">全部署</option>
                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                    {{ unit.name }}
                </option>
            </select>
        </div>

        <!-- 検索フィールドと結果表示 -->
        <div class="w-full sm:w-auto space-y-2">
            <div class="relative">
                <input
                    type="text"
                    v-model="searchQuery"
                    @input="emit('update:searchQuery', searchQuery)"
                    placeholder="職員名で検索..."
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm pl-10 pr-10 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                />
                <!-- 検索アイコン -->
                <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                    <i class="bi bi-search text-gray-400 dark:text-gray-500"></i>
                </div>
                <!-- リセットアイコン -->
                <div
                    v-if="searchQuery"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                    @click="resetSearch"
                >
                    <i class="bi bi-x-lg text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"></i>
                </div>
            </div>
            <!-- 検索結果件数表示 -->
            <div v-if="searchQuery" class="text-sm text-gray-600">
                検索結果: {{ totalResults }}件
            </div>
        </div>
    </div>
</template>
