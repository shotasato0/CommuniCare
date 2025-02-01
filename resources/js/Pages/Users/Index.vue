<script setup>
import { Head, usePage, Link, router } from "@inertiajs/vue3";
import { ref, watchEffect, computed } from "vue";
import Show from "@/Pages/Users/Show.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { deleteItem } from "@/Utils/deleteItem";
import UserSearchForm from "./Components/UserSearchForm.vue";
import { useDialog } from "@/composables/dialog";
import CustomDialog from "@/Components/CustomDialog.vue";

const { props } = usePage();
const users = ref(props.users);
const currentAdminId = props.currentAdminId;
const units = props.units;
const flashMessage = ref(props.flash.success || null);
const showDeleteButtons = ref(false);

const isUserProfileVisible = ref(false);
const selectedUser = ref(null);
const isAdminMode = ref(false);
const confirmationDialog = ref(false);
const targetUser = ref(null);

const selectedUnit = ref("");
const searchQuery = ref("");

// 検索フォームからの更新を処理
const updateSelectedUnit = (newValue) => {
    selectedUnit.value = newValue;
};

const updateSearchQuery = (newValue) => {
    searchQuery.value = newValue;
};

const openUserProfile = (user) => {
    if (!showDeleteButtons.value) {
        selectedUser.value = user;
        isUserProfileVisible.value = true;
    }
};

const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};

const dialog = useDialog();

const deleteUser = async (user) => {
    // ダイアログを表示して削除確認
    const result = await dialog.showDialog(
        `${user.name}さんを削除してもよろしいですか？`
    );

    if (!result) {
        console.log("削除がキャンセルされました");
        return;
    }

    // 削除処理を実行
    deleteItem("user", user.id, (deletedUserId) => {
        const index = users.value.findIndex((u) => u.id === deletedUserId);
        if (index !== -1) {
            users.value.splice(index, 1);
        }
        // 削除成功時にフラッシュメッセージを設定
        flashMessage.value = "職員が削除されました";
        showDeleteButtons.value = false;
    });
};

const handleAdminTransfer = (user) => {
    if (!isAdminMode.value) return;

    targetUser.value = user;
    confirmationDialog.value = true;
};

const executeAdminTransfer = async () => {
    try {
        await router.post(
            route("admin.transferAdmin"),
            { new_admin_id: targetUser.value.id },
            {
                onSuccess: () => {
                    flashMessage.value = `${targetUser.value.name}に管理者権限を譲渡しました`;
                    confirmationDialog.value = false;
                    targetUser.value = null;
                },
                onError: (errors) => {
                    const errorMessage =
                        errors.message || "管理者権限の移動に失敗しました";
                    console.error("エラー:", errorMessage);
                    flashMessage.value = errorMessage;
                },
            }
        );
    } catch (error) {
        console.error("予期しないエラー:", error);
        flashMessage.value = "管理者権限の移動中に問題が発生しました";
    }
};

// flashMessageの変更を監視して、8秒後にフラッシュメッセージをクリア
watchEffect(() => {
    if (flashMessage.value) {
        const timeout = setTimeout(() => {
            flashMessage.value = null;
        }, 8000);
        // クリーンアップでタイムアウトをクリア
        return () => clearTimeout(timeout);
    }
});

// 管理者かどうかを判定する関数
const isAdmin = computed(() => {
    return currentAdminId === props.auth.user.id;
});

// 管理者とその他ユーザーの分類
const sortedUsers = computed(() => {
    const currentAdmin = users.value.find((user) => user.id === currentAdminId);
    const otherUsers = users.value.filter((user) => user.id !== currentAdminId);
    return { currentAdmin, otherUsers };
});

// showDeleteButtonsの更新時にisAdminModeをfalseに
const toggleDeleteMode = () => {
    showDeleteButtons.value = !showDeleteButtons.value;
    if (showDeleteButtons.value) {
        isAdminMode.value = false;
    }
};

// isAdminModeの更新時にshowDeleteButtonsをfalseに
const toggleAdminMode = () => {
    isAdminMode.value = !isAdminMode.value;
    if (isAdminMode.value) {
        showDeleteButtons.value = false;
    }
};

// フィルタリングされた職員リストを返す算出プロパティ
const groupedUsers = computed(() => {
    // 検索クエリを小文字に変換
    const query = searchQuery.value.trim().toLowerCase();

    // 検索とフィルタリングの共通ロジック
    const filterUsers = (users) => {
        return users
            .filter((user) => {
                const matchesSearch =
                    !query || user.name.toLowerCase().includes(query);
                return matchesSearch;
            })
            .sort((a, b) => a.name.localeCompare(b.name, "ja"));
    };

    // 特定の部署が選択されている場合
    if (selectedUnit.value) {
        const usersInUnit = filterUsers(
            users.value.filter(
                (user) => user.unit_id === Number(selectedUnit.value)
            )
        );

        if (usersInUnit.length > 0) {
            return {
                [units.find((u) => u.id === Number(selectedUnit.value)).name]:
                    usersInUnit,
            };
        }
        return {};
    }

    // 全部署表示の場合
    const grouped = units.reduce((acc, unit) => {
        const usersInUnit = filterUsers(
            users.value.filter((user) => user.unit_id === unit.id)
        );

        if (usersInUnit.length > 0) {
            acc[unit.name] = usersInUnit;
        }
        return acc;
    }, {});

    // 未所属の職員を抽出
    const unassignedUsers = filterUsers(
        users.value.filter((user) => !user.unit_id)
    );

    // 未所属の職員がいる場合、未所属グループを追加
    if (unassignedUsers.length > 0) {
        grouped["未所属"] = unassignedUsers;
    }

    return grouped;
});

// 検索結果の総数を計算
const totalFilteredUsers = computed(() => {
    return Object.values(groupedUsers.value).flat().length;
});
</script>

<template>
    <Head :title="$t('Staff')" />

    <AuthenticatedLayout>
        <!-- カスタムダイアログ -->
        <CustomDialog
            :is-visible="dialog.state.isVisible"
            :message="dialog.state.message"
            @confirm="dialog.confirm"
            @cancel="dialog.cancel"
        />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t("Staff") }}
            </h2>
        </template>
        <!-- フラッシュメッセージ -->
        <transition
            enter-active-class="transition ease-in-out duration-300"
            enter-from-class="opacity-0 transform translate-y-2"
            enter-to-class="opacity-100 transform translate-y-0"
            leave-active-class="transition ease-in-out duration-300"
            leave-from-class="opacity-100 transform translate-y-0"
            leave-to-class="opacity-0 transform translate-y-2"
        >
            <div
                v-if="flashMessage"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg z-50"
            >
                <p class="font-bold text-center">{{ flashMessage }}</p>
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
                                <!-- 検索フォーム -->
                                <UserSearchForm
                                    :units="units"
                                    :selected-unit-id="selectedUnit"
                                    :total-results="totalFilteredUsers"
                                    @update:selected-unit="updateSelectedUnit"
                                    @update:search-query="updateSearchQuery"
                                    class="order-2 sm:order-1"
                                />
                            </div>
                        </div>

                        <!-- 管理者のみに表示するボタン群 -->
                        <div
                            v-if="isAdmin"
                            class="flex items-center space-x-4 order-1 sm:order-2"
                        >
                            <Link
                                :href="route('register')"
                                class="w-32 px-4 py-2 bg-blue-100 text-blue-700 rounded-md transition hover:bg-blue-300 hover:text-white text-center"
                            >
                                新規登録
                            </Link>
                            <button
                                @click="toggleDeleteMode"
                                class="w-32 px-4 py-2 rounded-md transition bg-red-100 text-red-700 hover:bg-red-300 hover:text-white"
                                :class="
                                    showDeleteButtons
                                        ? 'bg-red-300 !text-white'
                                        : 'bg-red-200 text-red-600'
                                "
                            >
                                削除モード
                            </button>
                            <button
                                @click="toggleAdminMode"
                                class="w-32 px-4 py-2 rounded-md transition bg-purple-100 text-purple-700 hover:bg-purple-300 hover:text-white"
                                :class="
                                    isAdminMode
                                        ? 'bg-purple-300 !text-white'
                                        : 'bg-purple-200 text-purple-600'
                                "
                            >
                                管理者権限
                            </button>
                        </div>

                        <!-- 削除モード説明（管理者かつ削除モードの時のみ表示） -->
                        <div
                            v-if="isAdmin && showDeleteButtons"
                            class="mt-4 p-4 bg-red-100 rounded-lg"
                        >
                            <p class="text-red-700">
                                削除したい職員をクリックすると削除できます。
                                この操作は取り消しできませんのでご注意ください。
                            </p>
                        </div>

                        <!-- 既存の管理者モード説明 -->
                        <div
                            v-if="isAdminMode"
                            class="mb-4 p-4 bg-purple-100 rounded-lg"
                        >
                            <p class="text-purple-700">
                                管理者権限を他の職員に渡すことができます。
                                この操作を行うと、現在の管理者権限が解除されます。
                            </p>
                        </div>

                        <!-- ユーザー一覧 -->
                        <div class="bg-white shadow rounded-lg p-4 mt-12">
                            <!-- 部署ごとのユーザー一覧 -->
                            <div
                                v-for="(unitUsers, unitName) in groupedUsers"
                                :key="unitName"
                                class="mb-8"
                            >
                                <!-- 部署見出し -->
                                <h3
                                    class="text-xl font-bold text-gray-800 border-b-2 border-gray-200 pb-2 mb-4"
                                >
                                    {{ unitName }}
                                    <span
                                        class="text-sm font-normal text-gray-600 ml-2"
                                    >
                                        ({{ unitUsers.length }}名)
                                    </span>
                                </h3>

                                <!-- 部署ごとの職員一覧 -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                                >
                                    <div
                                        v-for="user in unitUsers"
                                        :key="user.id"
                                        :class="[
                                            'relative block bg-white border rounded-lg p-4 shadow-sm transition-all text-gray-900 group cursor-pointer',
                                            showDeleteButtons
                                                ? 'hover:bg-red-50 cursor-pointer'
                                                : isAdminMode
                                                ? 'hover:bg-purple-50 cursor-pointer'
                                                : 'hover:bg-gray-50 hover:shadow-md',
                                        ]"
                                        @click="
                                            showDeleteButtons
                                                ? deleteUser(user)
                                                : isAdminMode
                                                ? handleAdminTransfer(user)
                                                : openUserProfile(user)
                                        "
                                    >
                                        <div
                                            class="flex items-center space-x-4"
                                        >
                                            <img
                                                :src="
                                                    user.icon
                                                        ? `/storage/${user.icon}`
                                                        : '/images/default_user_icon.png'
                                                "
                                                alt="Profile Icon"
                                                class="w-12 h-12 rounded-full"
                                            />
                                            <div
                                                class="flex justify-between items-start w-full"
                                            >
                                                <div class="flex items-center">
                                                    <span
                                                        :class="[
                                                            'font-bold text-lg transition-colors',
                                                            showDeleteButtons
                                                                ? 'text-gray-500 group-hover:text-red-500'
                                                                : isAdminMode
                                                                ? 'text-gray-500 group-hover:text-purple-500'
                                                                : 'text-gray-500 group-hover:text-black',
                                                        ]"
                                                    >
                                                        {{ user.name }}
                                                    </span>
                                                    <!-- 管理者アイコンとテキスト -->
                                                    <div
                                                        v-if="
                                                            user.id ===
                                                            currentAdminId
                                                        "
                                                        class="flex items-center ml-2"
                                                    >
                                                        <i
                                                            class="bi bi-award-fill text-yellow-500 text-xl"
                                                        ></i>
                                                        <span
                                                            class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full ml-1"
                                                        >
                                                            現在の管理者
                                                        </span>
                                                    </div>
                                                </div>
                                                <i
                                                    v-if="showDeleteButtons"
                                                    class="bi bi-trash text-red-500"
                                                ></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 表示する職員がいない場合 -->
                            <div
                                v-if="Object.keys(groupedUsers).length === 0"
                                class="text-center text-gray-500 mt-4 text-lg font-medium"
                            >
                                検索条件に一致する職員が見つかりませんでした。
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 確認ダイアログ -->
        <div
            v-if="confirmationDialog"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
        >
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">管理者権限の移動確認</h3>
                <p class="mb-4">
                    {{ targetUser?.name }}さんに管理者権限を渡しますか？
                    この操作を行うと、あなたの管理者権限は失われます。
                </p>
                <div class="flex justify-end space-x-4">
                    <button
                        @click="confirmationDialog = false"
                        class="bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                    >
                        キャンセル
                    </button>
                    <button
                        @click="executeAdminTransfer"
                        class="px-4 py-2 rounded-md transition bg-purple-100 text-purple-700 hover:bg-purple-300 hover:text-white"
                    >
                        管理者権限を渡す
                    </button>
                </div>
            </div>
        </div>

        <!-- ユーザー詳細モーダル -->
        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show v-if="selectedUser" :user="selectedUser" :units="units" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
