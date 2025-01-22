<script setup>
import { usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
import Footer from "@/Layouts/Footer.vue";

defineProps({
    adminExists: Boolean,
});

const page = usePage();
const tenant = page.props.tenant || {};

let currentUrl; // 現在のページURLを格納する変数
let guestTenantUrl; // 環境変数で指定されたゲストテナントのURL
let isGuestHome = false; // 現在のページがゲストホームかどうかのフラグ

// フラッシュメッセージの処理
const { props } = usePage();
const flash = props.flash;
const flashMessage = ref(flash.message || null);
const showFlashMessage = ref(!!flashMessage.value);

// フラッシュメッセージを8秒後に非表示にする
if (showFlashMessage.value) {
    setTimeout(() => {
        showFlashMessage.value = false;
    }, 8000);
}

// try-catchでエラーハンドリング
try {
    currentUrl = new URL(window.location.href); // 現在のURLをインスタンス化
    guestTenantUrl = new URL(
        import.meta.env.VITE_GUEST_TENANT_URL || "http://guestdemo.localhost" // ゲストテナントのURLをインスタンス化
    );

    isGuestHome =
        currentUrl.hostname === guestTenantUrl.hostname &&
        currentUrl.pathname === "/home"; // ゲストホームかどうかを判定
} catch (error) {
    // エラーが発生した場合の処理を記述
    console.error("URLの処理中にエラーが発生しました:", error);
}
</script>

<template>
    <!-- フラッシュメッセージ -->
    <transition name="fade">
        <div
            v-if="flashMessage && showFlashMessage"
            class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md mx-auto sm:rounded-lg shadow-lg text-center bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4"
        >
            <p class="font-bold">{{ flashMessage }}</p>
        </div>
    </transition>

    <div
        class="min-h-screen bg-gradient-to-b from-blue-50 to-white flex flex-col justify-center items-center text-center px-4"
    >
        <!-- メインコンテンツ -->
        <div
            class="flex flex-col sm:flex-row items-center justify-center w-full max-w-7xl gap-8 sm:gap-16"
        >
            <!-- 左側の画像 -->
            <img
                src="/images/top_image.png"
                alt="Welcome Image"
                class="w-full max-w-sm sm:w-2/3 lg:w-1/3 object-contain"
            />

            <!-- 右側のタイトルと説明文 -->
            <div
                class="flex flex-col items-center sm:items-start text-center sm:text-left mt-6 sm:mt-0"
            >
                <!-- タイトルロゴ -->
                <div class="mb-6 w-full max-w-md">
                    <ApplicationLogo
                        class="w-full text-blue-900 text-5xl md:text-5xl"
                    />
                    <div
                        class="w-1/2 sm:w-full h-1 bg-blue-900 mx-auto sm:mx-0 mt-4"
                    ></div>
                </div>

                <!-- タイトル -->
                <h2
                    class="text-blue-900 text-xl sm:text-2xl font-semibold mb-4 sm:mb-6"
                >
                    職員の効率的なコミュニケーションと情報管理を支援
                </h2>

                <!-- 説明文 -->
                <p
                    class="text-gray-700 text-base sm:text-lg max-w-sm sm:max-w-lg mb-6 leading-relaxed"
                >
                    CommuniCareは、介護施設の運営を効率化するために、
                    職員間のスムーズなコミュニケーションと利用者様の情報を一元管理する
                    プラットフォームです。
                </p>

                <!-- テナント名 -->
                <div
                    v-if="tenant?.business_name"
                    class="text-blue-900 text-lg sm:text-xl font-medium bg-blue-50 px-6 py-2 rounded-lg shadow-sm border border-blue-100 mb-6"
                >
                    {{ tenant.business_name }}
                </div>

                <!-- ボタンセクション -->
                <div
                    class="flex flex-wrap justify-center sm:justify-start gap-4"
                >
                    <!-- ボタンの切り替え -->
                    <Link
                        v-if="isGuestHome"
                        :href="route('guest.user.login')"
                        class="bg-blue-100 text-blue-700 hover:bg-blue-300 hover:text-white text-base sm:text-lg py-3 px-6 sm:py-4 sm:px-8 rounded-lg shadow-lg"
                    >
                        ゲストユーザーとしてログイン
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="bg-gray-200 text-gray-700 hover:bg-gray-400 hover:text-white text-base sm:text-lg py-3 px-6 sm:py-4 sm:px-8 rounded-lg shadow-lg"
                    >
                        ログイン
                    </Link>

                    <!-- 管理者登録ボタン -->
                    <Link
                        v-if="!adminExists"
                        :href="route('register-admin.form')"
                        class="bg-blue-100 text-blue-700 hover:bg-blue-300 hover:text-white text-base sm:text-lg py-3 px-6 sm:py-4 sm:px-8 rounded-lg shadow-lg"
                    >
                        管理者登録
                    </Link>
                </div>
            </div>
        </div>
    </div>
    <Footer />
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
