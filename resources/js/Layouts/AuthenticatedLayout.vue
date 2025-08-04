<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { redirectToForum } from "@/Utils/redirectToForum";
import Footer from "@/Layouts/Footer.vue";
import CustomDialog from "@/Components/CustomDialog.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";

const page = usePage();
const units = page.props.units || []; // Inertiaから`units`を取得
const users = page.props.users || []; // Inertiaから`users`を取得
const isGuest = page.props.isGuest || false; // Inertiaから`isGuest`を取得

// CSRFトークンを取得
const csrfToken = ref(
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || ""
);

const showingNavigationDropdown = ref(false);

// Inertiaからユーザー情報を取得
const auth = page.props.auth || { user: {} }; // userがnullの場合、デフォルト値を設定
const userUnitId = auth.user?.unit_id || null;

// ユーザーの情報を確認

// ダイアログの状態を管理
const isDialogVisible = ref(false);
const dialogMessage = ref("");
const dialogOnConfirm = ref(null);

// ダイアログを表示する関数
const showDialog = ({ message, onConfirm }) => {
    dialogMessage.value = message;
    dialogOnConfirm.value = onConfirm;
    isDialogVisible.value = true;
};

// ダイアログを非表示にする関数
const hideDialog = () => {
    isDialogVisible.value = false;
};

const navigateToForum = () => {
    redirectToForum(units, users, userUnitId, "forum.index", showDialog);
};

// フォーム送信用のref
const logoutForm = ref(null);

// ログアウト処理
const handleLogout = async (event) => {
    event.preventDefault();

    // ゲストユーザーの場合の確認
    if (auth.user?.guest_session_id) {
        const confirmed = window.confirm(
            "ログアウトするとゲストユーザーが削除されます。本当にログアウトしてよろしいですか？"
        );
        if (!confirmed) {
            return;
        }
    }

    // フォームを直接参照して送信
    logoutForm.value?.submit();
};

const isForumPage = ref(window.location.pathname === "/forum");
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 pb-16">
            <nav
                class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 fixed top-0 left-0 w-full z-10"
            >
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <div
                                    @click="navigateToForum"
                                    class="cursor-pointer"
                                >
                                    <ApplicationLogo
                                        class="block h-6 sm:h-8 w-auto fill-current text-blue-900"
                                    />
                                </div>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden lg:flex space-x-8 -my-px ms-10">
                                <div
                                    @click.prevent="navigateToForum"
                                    :class="[
                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 cursor-pointer whitespace-nowrap',
                                        isForumPage
                                            ? 'text-blue-500 border-blue-500 focus:outline-none focus:border-blue-700'
                                            : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-500',
                                    ]"
                                >
                                    {{ $t("Forum") }}
                                </div>

                                <div
                                    :class="[
                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 cursor-pointer whitespace-nowrap',
                                        route().current('users.index')
                                            ? 'text-blue-500 border-blue-500 focus:outline-none focus:border-blue-700'
                                            : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-500',
                                    ]"
                                    @click="
                                        () => $inertia.get(route('users.index'))
                                    "
                                >
                                    {{ $t("Staff") }}
                                </div>

                                <div
                                    :class="[
                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 cursor-pointer whitespace-nowrap',
                                        route().current('residents.index')
                                            ? 'text-blue-500 border-blue-500 focus:outline-none focus:border-blue-700'
                                            : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-500',
                                    ]"
                                    @click="
                                        () =>
                                            $inertia.get(
                                                route('residents.index')
                                            )
                                    "
                                >
                                    {{ $t("Residents") }}
                                </div>

                                <div
                                    :class="[
                                        'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 cursor-pointer whitespace-nowrap',
                                        route().current('dashboard')
                                            ? 'text-blue-500 border-blue-500 focus:outline-none focus:border-blue-700'
                                            : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-100 hover:border-gray-300 dark:hover:border-gray-500',
                                    ]"
                                    @click="
                                        () => $inertia.get(route('dashboard'))
                                    "
                                >
                                    {{ $t("Dashboard") }}
                                </div>
                            </div>
                        </div>

                        <div class="hidden lg:flex lg:items-center">
                            <!-- ダークモード切り替え -->
                            <div class="mr-4">
                                <DarkModeToggle />
                            </div>
                            
                            <!-- テナント名を表示 -->
                            <div
                                v-if="page.props.tenant?.business_name"
                                class="hidden xl:block text-gray-500 dark:text-gray-400 mr-4 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-md text-sm font-medium"
                            >
                                {{ page.props.tenant.business_name }}
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-100 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                <!-- user.nameがnullでないことを確認 -->
                                                <span v-if="auth.user">
                                                    {{ auth.user.name }}
                                                </span>
                                                <span v-else> ゲスト </span>

                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            {{ $t("Profile") }}
                                        </DropdownLink>
                                        <form
                                            ref="logoutForm"
                                            :action="
                                                isGuest
                                                    ? route('logout-guest')
                                                    : route('logout')
                                            "
                                            method="post"
                                            class="inline"
                                        >
                                            <input
                                                type="hidden"
                                                name="_token"
                                                :value="csrfToken"
                                            />
                                            <button
                                                type="submit"
                                                class="block w-full text-left px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition duration-150 ease-in-out"
                                                @click="handleLogout"
                                            >
                                                {{ $t("Log Out") }}
                                            </button>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center lg:hidden my-auto">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 dark:focus:text-gray-100 transition duration-150 ease-in-out"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="lg:hidden"
                >
                    <div class="pt-2 pb-3 space-y-2">
                        <div
                            @click.prevent="navigateToForum"
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                isForumPage
                                    ? 'bg-indigo-100 dark:bg-indigo-900 text-blue-500 border-l-4 border-blue-500 focus:outline-none'
                                    : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Forum") }}
                        </div>

                        <div
                            @click="() => $inertia.get(route('users.index'))"
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                route().current('users.index')
                                    ? 'bg-indigo-100 dark:bg-indigo-900 text-blue-500 border-l-4 border-blue-500 focus:outline-none'
                                    : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Staff") }}
                        </div>

                        <div
                            @click="
                                () => $inertia.get(route('residents.index'))
                            "
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                route().current('residents.index')
                                    ? 'bg-indigo-100 dark:bg-indigo-900 text-blue-500 border-l-4 border-blue-500 focus:outline-none'
                                    : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Residents") }}
                        </div>

                        <div
                            @click="() => $inertia.get(route('dashboard'))"
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                route().current('dashboard')
                                    ? 'bg-indigo-100 dark:bg-indigo-900 text-blue-500 border-l-4 border-blue-500 focus:outline-none'
                                    : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Dashboard") }}
                        </div>
                    </div>

                    <div class="pt-2 pb-3 space-y-2">
                        <div
                            @click="() => $inertia.get(route('profile.edit'))"
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                route().current('profile.edit')
                                    ? 'bg-indigo-100 dark:bg-indigo-900 text-blue-500 border-l-4 border-blue-500 focus:outline-none'
                                    : 'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Profile") }}
                        </div>
                        <div
                            @click="handleLogout"
                            :class="[
                                'block px-1 py-2 text-lg font-medium leading-5 cursor-pointer whitespace-nowrap transition duration-150 ease-in-out',
                                'text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:text-gray-700 dark:focus:text-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700',
                            ]"
                        >
                            {{ $t("Log Out") }}
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-800 shadow mt-16" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="mt-16">
                <slot />
            </main>
        </div>
        <Footer />
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
</template>

<style scoped>
@media (max-width: 861px) {
    .lg\:flex {
        display: none;
    }

    .lg\:hidden {
        display: block;
    }

    .hidden.lg\:hidden {
        display: none;
    }
}

@media (min-width: 862px) {
    .hidden.lg\:flex {
        display: flex;
    }

    .lg\:hidden {
        display: none;
    }
}

.lg\:flex {
    align-items: center;
    height: 100%;
}

.relative {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 0.75rem;
}

@media (min-width: 1120px) {
    .xl\:block {
        display: block;
    }
}
</style>
