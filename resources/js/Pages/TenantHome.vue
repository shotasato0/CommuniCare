<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-b from-blue-50 to-white flex flex-col justify-center items-center text-center"
    >
        <!-- メインコンテンツ -->
        <div
            class="flex flex-col sm:flex-row items-center justify-center w-full max-w-7xl gap-16 px-8"
        >
            <!-- 左側の画像 -->
            <img
                src="/images/top_image.png"
                alt="Welcome Image"
                class="w-full sm:w-2/3 lg:w-1/3 object-contain"
            />

            <!-- 右側のタイトルと説明文 -->
            <div class="flex flex-col items-start text-left">
                <!-- タイトルロゴ -->
                <div class="mb-8 w-full">
                    <ApplicationLogo class="w-full text-blue-900 text-4xl" />
                    <div class="w-full h-1 bg-blue-900 mx-auto mt-4"></div>
                </div>

                <!-- タイトル -->
                <h2 class="text-blue-900 text-2xl font-semibold mb-6">
                    職員の効率的なコミュニケーションと情報管理を支援
                </h2>

                <!-- 説明文 -->
                <p class="text-gray-700 text-lg max-w-lg mb-10 leading-relaxed">
                    CommuniCareは、介護施設の運営を効率化するために、
                    職員間のスムーズなコミュニケーションと利用者様の情報を一元管理する
                    プラットフォームです。
                </p>

                <!-- ボタンセクション -->
                <div class="flex flex-wrap gap-6">
                    <!-- ログインボタン -->
                    <Link
                        :href="route('login')"
                        class="bg-gray-300 text-gray-700 text-lg py-4 px-8 rounded-lg shadow-lg hover:bg-gray-400 transition transform hover:scale-105"
                    >
                        ログイン
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* 必要に応じてカスタムスタイルを追加可能 */
</style>
