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
        default: "",
    },
});

const showDeleteButtons = ref(false);
const selectedUnit = ref(props.selectedUnitId || "");

// フラッシュメッセージの状態管理
const localFlashMessage = ref(null);
const showFlashMessage = ref(false);

// フラッシュメッセージを表示する関数を先に定義
const displayFlashMessage = (message) => {
    if (!message) return;

    localFlashMessage.value = message;
    showFlashMessage.value = true;
    setTimeout(() => {
        showFlashMessage.value = false;
        localFlashMessage.value = null;
    }, 8000);
};

// フラッシュメッセージを監視（関数定義後に配置）
watch(
    () => usePage().props.flash,
    (newFlash) => {
        if (newFlash.success || newFlash.error) {
            displayFlashMessage(newFlash.success || newFlash.error);
        }
    },
    { immediate: true }
);

// 削除処理
const deleteResident = (residentId) => {
    if (confirm("本当にこの利用者を削除しますか？")) {
        router.delete(route("residents.destroy", residentId), {
            preserveScroll: true,
            onSuccess: (page) => {
                showDeleteButtons.value = false;
                displayFlashMessage(page.props.flash.success);
            },
        });
    }
};

// 部署変更時の処理
watch(selectedUnit, (newUnitId) => {
    router.get(
        route("residents.index", {
            unit_id: newUnitId === "" ? null : newUnitId,
        }),
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
});

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

// 部署ごとにグループ化された利用者リストを返す算出プロパティ
const groupedResidents = computed(() => {
    if (selectedUnit.value) {
        // 特定の部署が選択されている場合
        const residentsInUnit = props.residents
            .filter(resident => resident.unit_id === Number(selectedUnit.value))
            .sort((a, b) => a.name.localeCompare(b.name, "ja"));

        return residentsInUnit.length > 0
            ? { [selectedUnitName.value]: residentsInUnit }
            : {};  // 空のオブジェクトを返して「利用者が登録されていません」を表示
    }

    // 全部署表示の場合、部署ごとにグループ化
    return props.units.reduce((acc, unit) => {
        const residentsInUnit = props.residents
            .filter(resident => resident.unit_id === unit.id)
            .sort((a, b) => a.name.localeCompare(b.name, "ja"));
        
        if (residentsInUnit.length > 0) {
            acc[unit.name] = residentsInUnit;
        }
        return acc;
    }, {});
});
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
                v-if="localFlashMessage && showFlashMessage"
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
                <p class="font-bold">{{ localFlashMessage }}</p>
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
                                v-if="Object.keys(groupedResidents).length > 0"
                            >
                                <div
                                    v-for="(
                                        residents, unitName
                                    ) in groupedResidents"
                                    :key="unitName"
                                    class="mb-8"
                                >
                                    <!-- 部署見出し -->
                                    <h3
                                        class="text-xl font-bold text-gray-800 border-b-2 border-gray-200 pb-2 mb-4"
                                    >
                                        {{ unitName }}
                                    </h3>

                                    <!-- 部署ごとの利用者一覧 -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-5 gap-4"
                                    >
                                        <div
                                            v-for="resident in residents"
                                            :key="resident.id"
                                            :class="[
                                                'relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group',
                                                showDeleteButtons
                                                    ? 'hover:bg-red-50 cursor-pointer'
                                                    : 'hover:bg-gray-50 hover:shadow-md',
                                            ]"
                                            @click="
                                                showDeleteButtons
                                                    ? deleteResident(
                                                          resident.id
                                                      )
                                                    : null
                                            "
                                        >
                                            <Link
                                                v-if="!showDeleteButtons"
                                                :href="
                                                    route(
                                                        'residents.show',
                                                        resident.id
                                                    )
                                                "
                                                class="block"
                                            >
                                                <div
                                                    class="flex justify-between items-start"
                                                >
                                                    <span
                                                        class="font-bold text-lg text-gray-500 group-hover:text-black transition-colors"
                                                    >
                                                        {{ resident.name }}
                                                    </span>
                                                </div>
                                            </Link>
                                            <div
                                                v-else
                                                class="flex justify-between items-start"
                                            >
                                                <span
                                                    class="font-bold text-lg text-gray-500 group-hover:text-red-500 transition-colors"
                                                >
                                                    {{ resident.name }}
                                                </span>
                                                <i
                                                    class="bi bi-trash text-red-500"
                                                ></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
