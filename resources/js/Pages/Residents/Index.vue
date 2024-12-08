<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { router, Link, usePage } from "@inertiajs/vue3";
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
        type: [Number, String],
        default: null,
    },
});

const showDeleteButtons = ref(false);
const selectedUnit = ref(props.selectedUnitId);

// 部署変更時の処理
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

// 選択された部署名を取得する算出プロパティを追加
const selectedUnitName = computed(() => {
    if (selectedUnit.value === "") return "全部署";
    const unit = props.units.find(
        (unit) => String(unit.id) === String(selectedUnit.value)
    );
    return unit ? unit.name : "全部署";
});

// ソートされた利用者リストを返す算出プロパティを追加
const sortedResidents = computed(() => {
    return [...props.residents].sort((a, b) =>
        a.name.localeCompare(b.name, "ja")
    );
});

// フラッシュメッセージを取得
const flash = computed(() => {
    console.log("Flash props:", usePage().props.flash);
    return usePage().props.flash;
});

// フラッシュメッセージの表示制御
const showFlashMessage = ref(true);
const flashMessage = computed(
    () => flash.value.success || flash.value.error || flash.value.info || null
);

// 削除モードを解除するためのクリックイベントハンドラを追加
const handleClickOutside = (event) => {
    const deleteButtons = document.querySelectorAll(".delete-mode-button");
    const isClickInsideButton = Array.from(deleteButtons).some((button) =>
        button.contains(event.target)
    );

    if (showDeleteButtons.value && !isClickInsideButton) {
        showDeleteButtons.value = false;
    }
};

// コンポーネントがマウントされた時にイベントリスナーを追加
onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
            showFlashMessage.value = false;
        }, 8000);
    }

    // クリックイベントリスナーを追加
    document.addEventListener("click", handleClickOutside);
});

// コンポーネントがアンマウントされる時にイベントリスナーを削除
onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

// フラッシュメッセージのタイプを判定
const flashType = computed(() =>
    flash.value.success ? "success" : flash.value.error ? "error" : "info"
);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                利用者一覧
            </h2>
        </template>

        <!-- フラッシュメッセージの条件を修正 -->
        <transition name="fade">
            <div
                v-if="flashMessage && showFlashMessage"
                :class="{
                    'bg-green-100 border-l-4 border-green-500 text-green-700 p-4':
                        flashType === 'success',
                    'bg-red-100 border-l-4 border-red-500 text-red-700 p-4':
                        flashType === 'error',
                    'bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4':
                        flashType === 'info',
                }"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md mx-auto sm:rounded-lg shadow-lg z-50"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- コントロール部分 -->
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center space-x-4">
                                <select
                                    v-model="selectedUnit"
                                    class="rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="">全部署</option>
                                    <option
                                        v-for="unit in units"
                                        :key="unit.id"
                                        :value="unit.id"
                                    >
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-4">
                                <!-- 新規登録ボタン -->
                                <Link
                                    :href="route('residents.create')"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md transition hover:bg-opacity-80"
                                >
                                    新規登録
                                </Link>

                                <!-- 削除モードトグルボタン -->
                                <button
                                    @click="
                                        showDeleteButtons = !showDeleteButtons
                                    "
                                    class="px-4 py-2 text-white rounded-md transition delete-mode-button"
                                    :class="
                                        showDeleteButtons
                                            ? 'bg-red-300 text-red-700 hover:bg-opacity-80'
                                            : 'bg-red-500 hover:bg-opacity-80'
                                    "
                                >
                                    {{
                                        showDeleteButtons
                                            ? "削除モード"
                                            : "削除モード"
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- 部署見出しを追加 -->
                        <div class="mb-6">
                            <h3
                                class="text-2xl font-bold text-gray-800 border-b-2 border-gray-200 pb-2"
                            >
                                {{ selectedUnitName }}
                            </h3>
                        </div>

                        <!-- 利用者一覧 -->
                        <div class="bg-white shadow rounded-lg p-4">
                            <!-- 利用者が存在する場合 -->
                            <div
                                v-if="sortedResidents.length > 0"
                                class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-5 gap-4"
                            >
                                <Link
                                    v-for="resident in sortedResidents"
                                    :key="resident.id"
                                    :href="route('residents.show', resident.id)"
                                    class="relative block bg-white hover:bg-gray-50 border rounded-lg p-4 shadow-sm hover:shadow-md transition-all text-gray-900 group"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <span
                                            class="font-bold text-lg text-gray-500 group-hover:text-black transition-colors"
                                        >
                                            {{ resident.name }}
                                        </span>
                                        <button
                                            v-if="showDeleteButtons"
                                            @click.prevent="
                                                deleteResident(resident.id)
                                            "
                                            class="text-red-500 hover:text-red-700 transition"
                                            title="削除"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </Link>
                            </div>

                            <!-- 利用者が存在しない場合 -->
                            <div v-else class="p-8 text-center text-gray-500">
                                <i class="bi bi-people text-4xl mb-2 block"></i>
                                <p class="text-lg font-medium">
                                    利用者が登録されていません。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}
</style>
