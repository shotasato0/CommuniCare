<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { router, Link, usePage, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SearchForm from "./Components/SearchForm.vue";
import CustomDialog from "@/Components/CustomDialog.vue";
import { useDialog } from "@/composables/dialog";

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

const dialog = useDialog();

// 削除処理
const deleteResident = async (resident) => {
    const result = await dialog.showDialog(
        `${resident.name}さんを削除してもよろしいですか？`
    );
    if (!result) {
        console.log("削除がキャンセルされました");
        return;
    }

    router.delete(route("residents.destroy", resident.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            showDeleteButtons.value = false;
            displayFlashMessage(page.props.flash.success);
        },
    });
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
            .filter(
                (resident) => resident.unit_id === Number(selectedUnit.value)
            )
            .sort((a, b) => a.name.localeCompare(b.name, "ja"));

        return residentsInUnit.length > 0
            ? { [selectedUnitName.value]: residentsInUnit }
            : {}; // 空のオブジェクトを返して「利用者が登録されていません」を表示
    }

    // 全部署表示の場合、部署ごとにグループ化とソート
    return props.units.reduce((acc, unit) => {
        const residentsInUnit = props.residents
            .filter((resident) => resident.unit_id === unit.id)
            .sort((a, b) => a.name.localeCompare(b.name, "ja"));

        if (residentsInUnit.length > 0) {
            acc[unit.name] = residentsInUnit;
        }
        return acc;
    }, {});
});

// 検索用の状態を追加
const searchQuery = ref("");

// グループ化された利用者リストを検索クエリでフィルタリング
const filteredGroupedResidents = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    // 検索クエリが空の場合は全ての結果を返す
    if (!query) return groupedResidents.value;

    // 各部署の利用者をフィルタリングとソート
    const filtered = {};
    Object.entries(groupedResidents.value).forEach(([unitName, residents]) => {
        const filteredResidents = residents
            .filter((resident) => resident.name.toLowerCase().includes(query))
            .sort((a, b) => a.name.localeCompare(b.name, "ja")); // ソート処理を追加

        if (filteredResidents.length > 0) {
            filtered[unitName] = filteredResidents;
        }
    });

    return filtered;
});

// 検索関連の computed プロパティを追加
const totalResidents = computed(() => {
    return Object.values(filteredGroupedResidents.value).reduce(
        (total, residents) => total + residents.length,
        0
    );
});

// v-modelバインディング用のemits
const updateSearchQuery = (query) => {
    searchQuery.value = query;
};

const updateSelectedUnit = (unit) => {
    selectedUnit.value = unit;
};

// propsの二重定義を避けるため、page.propsを直接使用
const page = usePage();
const isAdmin = computed(() => page.props.isAdmin);
</script>

<template>
    <AuthenticatedLayout>
        <!-- カスタムダイアログ -->
        <CustomDialog
            :is-visible="dialog.state.isVisible"
            :message="dialog.state.message"
            @confirm="dialog.confirm"
            @cancel="dialog.cancel"
        />

        <Head :title="$t('Residents')" />
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Residents") }}
            </h2>
        </template>

        <!-- フラッシュメッセージのスタイル -->
        <transition
            enter-active-class="transition ease-in-out duration-300"
            enter-from-class="opacity-0 transform translate-y-2"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition ease-in-out duration-300"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform translate-y-2"
        >
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
                <p class="font-bold text-center">{{ localFlashMessage }}</p>
            </div>
        </transition>

        <div class="pb-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- コントロール部分のコンテナ -->
                        <div class="mb-6">
                            <!-- ボタンと検索フォームの横並び部分 -->
                            <div
                                class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:justify-between sm:items-start"
                            >
                                <!-- 管理者のみに表示するボタン群 -->
                                <div
                                    v-if="isAdmin"
                                    class="flex items-center space-x-4 order-1 sm:order-2"
                                >
                                    <!-- 新規登録ボタン -->
                                    <Link
                                        :href="route('residents.create')"
                                        class="w-32 px-4 py-2 bg-blue-100 text-blue-700 rounded-md transition hover:bg-blue-300 hover:text-white text-center"
                                    >
                                        新規登録
                                    </Link>

                                    <!-- 削除モードトグルボタン -->
                                    <button
                                        @click="
                                            showDeleteButtons =
                                                !showDeleteButtons
                                        "
                                        class="w-32 px-4 py-2 rounded-md transition delete-mode-button bg-red-100 text-red-700 hover:bg-red-300 hover:text-white text-center"
                                        :class="
                                            showDeleteButtons
                                                ? 'bg-red-300 !text-white'
                                                : 'bg-red-200 text-red-600'
                                        "
                                    >
                                        削除モード
                                    </button>
                                </div>

                                <!-- 検索フォーム -->
                                <SearchForm
                                    :units="units"
                                    :selected-unit-id="selectedUnit"
                                    :total-results="totalResidents"
                                    @update:search-query="updateSearchQuery"
                                    @update:selected-unit="updateSelectedUnit"
                                    class="order-2 sm:order-1"
                                />
                            </div>

                            <!-- 削除モード説明（管理者かつ削除モードの時のみ表示） -->
                            <div
                                v-if="isAdmin && showDeleteButtons"
                                class="mt-4 p-4 bg-red-100 rounded-lg"
                            >
                                <p class="text-red-700">
                                    削除したい利用者をクリックすると削除できます。
                                    この操作は取り消しできませんのでご注意ください。
                                </p>
                            </div>
                        </div>

                        <!-- 利用者一覧 -->
                        <div class="bg-white shadow rounded-lg p-4 mt-12">
                            <!-- 利用者が存在する場合 -->
                            <div
                                v-if="
                                    Object.keys(filteredGroupedResidents)
                                        .length > 0
                                "
                            >
                                <div
                                    v-for="(
                                        residents, unitName
                                    ) in filteredGroupedResidents"
                                    :key="unitName"
                                    class="mb-8"
                                >
                                    <!-- 部署見出し（件数も表示）-->
                                    <h3
                                        class="text-xl font-bold text-gray-800 border-b-2 border-gray-200 pb-2 mb-4"
                                    >
                                        {{ unitName }}
                                        <span
                                            class="text-sm font-normal text-gray-600 ml-2"
                                        >
                                            ({{ residents.length }}名)
                                        </span>
                                    </h3>

                                    <!-- 部署ごとの利用者一覧 -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-5 gap-4"
                                    >
                                        <div
                                            v-for="resident in residents"
                                            :key="resident.id"
                                        >
                                            <!-- 削除モード時 -->
                                            <div
                                                v-if="showDeleteButtons"
                                                :class="[
                                                    'relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group hover:bg-red-50 cursor-pointer',
                                                ]"
                                                @click="
                                                    deleteResident(resident)
                                                "
                                            >
                                                <div
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

                                            <!-- 通常モード時 -->
                                            <Link
                                                v-else
                                                :href="
                                                    route(
                                                        'residents.show',
                                                        resident.id
                                                    )
                                                "
                                                :class="[
                                                    'relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group hover:bg-gray-50 hover:shadow-md',
                                                ]"
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
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 検索結果が存在しない場合 -->
                            <div v-else class="p-8 text-center text-gray-500">
                                <i class="bi bi-people text-4xl mb-2 block"></i>
                                <p class="text-lg font-medium">
                                    {{
                                        searchQuery
                                            ? "検索条件に一致する利用者が見つかりません。"
                                            : "利用者が登録されていません。"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
