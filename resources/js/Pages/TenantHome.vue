<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    adminExists: Boolean,
});
</script>

<template>
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
                    <ApplicationLogo class="w-full text-blue-900 text-4xl" />
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

                <!-- ボタンセクション -->
                <div
                    class="flex flex-wrap justify-center sm:justify-start gap-4"
                >
                    <!-- ログインボタン -->
                    <Link
                        :href="route('login')"
                        class="bg-gray-300 text-gray-700 text-base sm:text-lg py-3 px-6 sm:py-4 sm:px-8 rounded-lg shadow-lg hover:bg-gray-400 transition transform hover:scale-105"
                    >
                        ログイン
                    </Link>

                    <!-- 管理者登録ボタン -->
                    <Link
                        v-if="!adminExists"
                        :href="route('register-admin.form')"
                        class="bg-blue-500 text-white text-base sm:text-lg py-3 px-6 sm:py-4 sm:px-8 rounded-lg shadow-lg hover:bg-blue-600 transition transform hover:scale-105"
                    >
                        管理者登録
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
