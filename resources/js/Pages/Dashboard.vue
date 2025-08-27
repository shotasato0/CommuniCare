<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import AdminDashboard from "@/Pages/Admin/Dashboard.vue";
import { redirectToForum } from "@/Utils/redirectToForum";
import Show from "@/Pages/Users/Show.vue";
import CustomDialog from "@/Components/CustomDialog.vue";

const { props } = usePage();
const isAdmin = props.isAdmin;
const flash = props.flash;
const isGuest = props.isGuest;

const flashMessage = ref(flash.success || flash.error || null);

const flashType = ref(
    flash.success ? "success" : flash.error ? "error" : "info"
);

// 8秒後にフラッシュメッセージをフェードアウトさせる
const showFlashMessage = ref(true);
onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
            showFlashMessage.value = false;
        }, 8000);
    }
});

const isDialogVisible = ref(false); // ダイアログの表示状態
const dialogMessage = ref(""); // ダイアログのメッセージ
const dialogOnConfirm = ref(null); // ダイアログのOKボタンの処理

// ダイアログを表示する関数
const showDialog = ({ message, onConfirm }) => {
    dialogMessage.value = message; // メッセージをセット
    dialogOnConfirm.value = onConfirm; // 配列ではなく関数をそのまま代入
    isDialogVisible.value = true; // ダイアログを表示
};

// ダイアログを非表示にする関数
const hideDialog = () => {
    isDialogVisible.value = false;
};

// 掲示板に移動する関数
const navigateToForum = () => {
    redirectToForum(
        props.units,
        props.users,
        props.auth.user.unit_id,
        "forum.index",
        showDialog
    );
};

// モーダル制御用の状態
const isUserProfileVisible = ref(false);
const selectedUser = ref(null);

// モーダルを開く関数
const openUserProfile = (user) => {
    selectedUser.value = user;
    isUserProfileVisible.value = true;
};

// モーダルを閉じる関数
const closeUserProfile = () => {
    isUserProfileVisible.value = false;
};
</script>

<template>
    <Head :title="$t('Dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $t("Dashboard") }}
            </h2>
        </template>

        <!-- ダッシュボードのコンテンツ全体に左右余白を追加 -->
        <div class="pb-12 px-4 sm:px-8 lg:px-16">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0"
                        >
                            <!-- 左側：ログインメッセージと掲示板リンク -->
                            <div class="flex items-center space-x-4">
                                <div v-if="!isGuest">
                                    {{ $t("You're logged in") }}
                                </div>
                                <div v-else>
                                    {{ $t("Logged in as guest user") }}
                                </div>
                                <button
                                    href="#"
                                    @click.prevent="navigateToForum"
                                    class="text-blue-500 link-hover whitespace-nowrap"
                                >
                                    {{ $t("Go to Forum") }}
                                </button>
                            </div>

                            <!-- 右側：ユーザーアイコンと名前 -->
                            <div
                                @click="openUserProfile(props.auth.user)"
                                class="flex items-center space-x-4 group hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 cursor-pointer"
                            >
                                <img
                                    :src="
                                        props.auth.user.icon 
                                            ? (props.auth.user.icon.startsWith('/storage/') 
                                                ? props.auth.user.icon 
                                                : `/storage/${props.auth.user.icon}`)
                                            : '/images/default_user_icon.png'
                                    "
                                    alt="Profile Icon"
                                    class="w-12 h-12 rounded-full"
                                    @error="$event.target.src='/images/default_user_icon.png'"
                                />
                                <div class="flex items-center">
                                    <span
                                        class="font-bold text-lg text-gray-500 dark:text-gray-300 group-hover:text-black dark:group-hover:text-white transition-colors"
                                    >
                                        {{ props.auth.user.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- フラッシュメッセージの表示 -->
        <transition name="fade">
            <div
                v-if="flashMessage && showFlashMessage"
                :class="{
                    'bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4':
                        flashType === 'success',
                    'bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4':
                        flashType === 'error',
                    'bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 text-blue-700 dark:text-blue-300 p-4':
                        flashType === 'info',
                }"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md mx-auto sm:rounded-lg shadow-lg text-center"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <!-- 管理者ページ -->
        <div v-if="isAdmin">
            <AdminDashboard />
        </div>

        <!-- モーダルコンポーネント -->
        <div
            v-if="isUserProfileVisible"
            class="fixed inset-0 bg-black/50 dark:bg-black/70 flex justify-center items-center z-50"
            @click="closeUserProfile"
        >
            <div @click.stop>
                <Show v-if="selectedUser" :user="selectedUser" :units="units" />
            </div>
        </div>

        <!-- カスタムダイアログ -->
        <CustomDialog
            :isVisible="isDialogVisible"
            :message="dialogMessage"
            :onConfirm="dialogOnConfirm"
            :showProfileLink="true"
            @confirm="hideDialog"
            @cancel="hideDialog"
        />
    </AuthenticatedLayout>
</template>

<style>
.link-hover:hover {
    color: #007bff;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}
</style>
