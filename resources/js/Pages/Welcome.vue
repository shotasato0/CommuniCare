<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import Footer from "@/Layouts/Footer.vue";
// フォーム定義
const form = useForm({
    business_name: "",
    tenant_domain_id: "",
    remember: false,
});

// フォーム送信処理
const submit = () => {
    form.post(route("tenant.register"), {
        preserveScroll: true,
        onError: () => {
            document.getElementById("registration-form")?.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        },
        onFinish: () => {
            if (Object.keys(form.errors).length === 0) {
                form.reset("business_name", "tenant_domain_id");
            }
        },
    });
};

const guestLogin = () => {
    // クロスドメイン（ゲストテナント）へはSPA経由にせずフルリロード
    const url = import.meta.env.VITE_GUEST_TENANT_URL;
    if (url) {
        window.location.assign(url);
    } else {
        // フォールバック（開発環境などで未設定の場合）
        window.location.href = "http://guestdemo.localhost";
    }
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-white">
        <Head :title="'施設登録'" />

        <!-- ヘッダー -->
        <header class="w-full py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <ApplicationLogo
                    class="w-full text-blue-900 text-3xl md:text-5xl"
                />
            </div>
        </header>

        <!-- メインコンテンツ -->
        <main>
            <!-- ヒーローセクション -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- 左側：テキストコンテンツ -->
                    <div class="space-y-6">
                        <h1
                            class="text-4xl font-bold text-gray-900 leading-tight"
                        >
                            職員間の<span class="text-blue-600"
                                >コミュニケーション</span
                            >を <br />もっとスムーズに
                        </h1>
                        <div class="space-y-4">
                            <p class="text-lg text-gray-600 leading-relaxed">
                                CommuniCareは、介護施設の職員間のコミュニケーションを活性化し、正確で円滑な情報伝達を可能にすることで、
                                利用者様の情報や連絡事項を安全かつ効率的に一元管理できるプラットフォームです。
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg
                                        class="h-6 w-6 text-blue-500 mt-0.5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                                        />
                                    </svg>
                                    <span class="ml-2 text-gray-600"
                                        >職員間の連携を強化する、タイムリーな情報共有</span
                                    >
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="h-6 w-6 text-blue-500 mt-0.5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                        />
                                    </svg>
                                    <span class="ml-2 text-gray-600"
                                        >利用者様の情報を安全に一元管理</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button
                                @click="guestLogin"
                                class="inline-flex items-center px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
                            >
                                <span class="mr-2">デモで体験する</span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- 右側：イラスト -->
                    <div class="relative hidden lg:block">
                        <img
                            src="/images/top_image.png"
                            alt="Welcome Image"
                            class="w-full object-contain max-h-[400px] lg:max-h-[500px]"
                        />
                    </div>
                </div>
            </div>

            <!-- 登録フォームセクション -->
            <div class="bg-white py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-xl mx-auto">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-semibold text-gray-900">
                                施設登録
                            </h2>
                            <p class="mt-2 text-gray-600">
                                専用の管理環境を作成できます
                            </p>

                            <!-- 注意書きを追加 -->
                            <div
                                class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg"
                            >
                                <p class="text-yellow-800 text-sm">
                                    現在、サブドメイン登録機能は提供を一時停止しております。<br />
                                    ゲストアカウントで試用版のサービスをご利用いただけます。
                                </p>
                                <p class="text-yellow-800 text-sm mt-2">
                                    専用のテナントやサブドメインをご希望の方は、<br />
                                    X（旧Twitter）のDMにてお問い合わせください。
                                </p>
                            </div>
                        </div>

                        <!-- フォームとドメインID説明を並べて配置 -->
                        <div class="grid md:grid-cols-5 gap-6">
                            <!-- フォーム：3カラム分 -->
                            <form
                                id="registration-form"
                                @submit.prevent="submit"
                                class="md:col-span-3 bg-white rounded-xl shadow-sm border p-6"
                            >
                                <div class="space-y-5">
                                    <div>
                                        <InputLabel
                                            for="business_name"
                                            value="施設名"
                                        />
                                        <TextInput
                                            id="business_name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.business_name"
                                            required
                                            autofocus
                                            placeholder="例：社会福祉法人コミュニケア"
                                            disabled
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.business_name"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            for="tenant_domain_id"
                                            value="施設ドメインID"
                                        />
                                        <TextInput
                                            id="tenant_domain_id"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.tenant_domain_id"
                                            required
                                            placeholder="例：example"
                                            disabled
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="
                                                form.errors.tenant_domain_id
                                            "
                                        />
                                    </div>

                                    <div class="pt-2">
                                        <PrimaryButton
                                            class="w-full justify-center"
                                            :class="{
                                                'opacity-25': form.processing,
                                            }"
                                            :disabled="true"
                                        >
                                            登録する
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </form>

                            <!-- ドメインID説明：2カラム分 -->
                            <div
                                class="md:col-span-2 flex flex-col justify-center"
                            >
                                <div class="bg-blue-50 rounded-xl p-5">
                                    <div
                                        class="flex items-center space-x-2 mb-3"
                                    >
                                        <svg
                                            class="h-5 w-5 text-blue-600 flex-shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <span class="font-medium text-blue-800"
                                            >ドメインIDについて</span
                                        >
                                    </div>
                                    <div class="text-sm text-blue-700">
                                        <p class="break-words">
                                            施設ドメインIDは、以下の形式でURLとなります：
                                        </p>
                                        <code
                                            class="block px-3 py-2 bg-blue-100 rounded text-xs mt-2 break-all"
                                        >
                                            https://example.communicare.com
                                        </code>
                                        <p class="text-xs text-blue-600 mt-2">
                                            ※英数字のみ使用可能です
                                        </p>
                                        <p class="mt-3">
                                            発行されたURLにアクセスすることで、以降は専用の管理画面からサービスをご利用いただけます。
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
