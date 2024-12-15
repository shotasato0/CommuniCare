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
    <div class="flex flex-col sm:flex-row items-start space-y-4 sm:space-y-0 sm:space-x-4">
        <!-- 部署選択プルダウン -->
        <div class="w-full sm:w-auto">
            <select
                v-model="selectedUnit"
                @change="handleUnitChange"
                class="w-full rounded-md border-gray-300 shadow-sm"
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
                    placeholder="社員名で検索..."
                    class="w-full rounded-md border-gray-300 shadow-sm pl-10 pr-10 focus:border-blue-500"
                />
                <!-- 検索アイコン -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <!-- リセットアイコン -->
                <div
                    v-if="searchQuery"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer"
                    @click="resetSearch"
                >
                    <i class="bi bi-x text-gray-400 hover:text-gray-600"></i>
                </div>
            </div>
            <!-- 検索結果件数表示 -->
            <div v-if="searchQuery" class="text-sm text-gray-600 absolute mt-1">
                検索結果: {{ totalResults }}件
            </div>
        </div>
    </div>
</template>
