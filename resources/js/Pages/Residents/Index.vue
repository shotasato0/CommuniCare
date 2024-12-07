<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    residents: {
        type: Object,
        required: true,
    },
    units: {
        type: Array,
        required: true,
    },
    selectedUnitId: {
        type: Number,
        default: null,
    },
});

const showDeleteButtons = ref(false);
const selectedUnit = ref(props.selectedUnitId);

// ユニット変更時の処理
watch(selectedUnit, (newUnitId) => {
    router.get(route("residents.index", { unit_id: newUnitId }), {
        preserveState: true,
        preserveScroll: true,
    });
});

// 利用者削除の処理
const deleteResident = (residentId) => {
    if (confirm("本当にこの利用者を削除しますか？")) {
        router.delete(route("residents.destroy", residentId), {
            preserveScroll: true,
            onSuccess: () => {
                // 削除成功時の処理
                showDeleteButtons.value = false;
            },
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- ヘッダー部分 -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <h2 class="text-2xl font-bold">利用者一覧</h2>
                    <select
                        v-model="selectedUnit"
                        class="rounded-md border-gray-300 shadow-sm"
                    >
                        <option value="">全ユニット</option>
                        <option
                            v-for="unit in units"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.name }}
                        </option>
                    </select>
                </div>

                <!-- 削除モードトグルボタン -->
                <button
                    @click="showDeleteButtons = !showDeleteButtons"
                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition"
                    :class="{ 'bg-gray-500': showDeleteButtons }"
                >
                    {{ showDeleteButtons ? "削除モード解除" : "削除モード" }}
                </button>
            </div>

            <!-- 利用者一覧 -->
            <div class="bg-white shadow rounded-lg">
                <!-- 利用者が存在する場合 -->
                <ul
                    v-if="residents.length > 0"
                    class="divide-y divide-gray-200"
                >
                    <li
                        v-for="resident in residents"
                        :key="resident.id"
                        class="flex justify-between items-center p-4 hover:bg-gray-50"
                    >
                        <div class="flex items-center space-x-4">
                            <img
                                :src="
                                    resident.avatar ||
                                    'https://via.placeholder.com/40'
                                "
                                class="w-10 h-10 rounded-full"
                                alt="利用者アイコン"
                            />
                            <div>
                                <p class="font-medium">{{ resident.name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ resident.unit?.name }}
                                </p>
                            </div>
                        </div>

                        <!-- 削除ボタン -->
                        <button
                            v-if="showDeleteButtons"
                            @click="deleteResident(resident.id)"
                            class="text-red-500 hover:text-red-700 transition"
                            title="削除"
                        >
                            <i class="bi bi-trash text-xl"></i>
                        </button>
                    </li>
                </ul>

                <!-- 利用者が存在しない場合 -->
                <div v-else class="p-8 text-center text-gray-500">
                    <i class="bi bi-people text-4xl mb-2 block"></i>
                    <p class="text-lg font-medium">
                        利用者が登録されていません。
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.transition {
    transition: all 0.3s ease;
}
</style>
