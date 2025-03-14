<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    resident: {
        type: Object,
        required: true,
    },
});

// フラッシュメッセージの処理
const flash = computed(() => usePage().props.flash);
const showFlashMessage = ref(true);
const flashMessage = computed(
    () => flash.value.success || flash.value.error || null
);

// フラッシュメッセージの自動非表示
onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
            showFlashMessage.value = false;
        }, 8000);
    }
});

// フラッシュメッセージのタイプを判定
const flashType = computed(() => (flash.value.success ? "success" : "error"));
</script>

<template>
    <Head :title="`${resident.name}さんの詳細情報`" />

    <AuthenticatedLayout>
        <!-- フラッシュメッセージ -->
        <transition name="fade">
            <div
                v-if="flashMessage && showFlashMessage"
                :class="{
                    'bg-green-100 border-l-4 border-green-500 text-green-700 p-4':
                        flashType === 'success',
                    'bg-red-100 border-l-4 border-red-500 text-red-700 p-4':
                        flashType === 'error',
                }"
                class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md mx-auto sm:rounded-lg shadow-lg z-50"
            >
                <p class="font-bold">{{ flashMessage }}</p>
            </div>
        </transition>

        <div class="pt-6 pb-12 mt-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-100 overflow-hidden rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-8 mb-6">
                            <h2 class="text-xl font-bold text-gray-800">
                                {{ resident.unit.name }}
                            </h2>
                            <h2 class="text-xl font-bold text-gray-800">
                                {{ resident.name }}さんの情報
                            </h2>
                        </div>

                        <div class="space-y-6">
                            <!-- 2x2 グリッドレイアウト -->
                            <div class="grid grid-cols-2 gap-6">
                                <!-- 食事の支援 -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        食事の支援
                                    </h3>
                                    <div class="mt-2">
                                        <div
                                            class="p-3 bg-white rounded-md border border-gray-300 min-h-[12rem] whitespace-pre-wrap"
                                        >
                                            {{ resident.meal_support }}
                                        </div>
                                    </div>
                                </div>

                                <!-- 排泄介助について -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        排泄介助について
                                    </h3>
                                    <div class="mt-2">
                                        <div
                                            class="p-3 bg-white rounded-md border border-gray-300 min-h-[12rem] whitespace-pre-wrap"
                                        >
                                            {{ resident.toilet_support }}
                                        </div>
                                    </div>
                                </div>

                                <!-- 入浴介助について -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        入浴介助について
                                    </h3>
                                    <div class="mt-2">
                                        <div
                                            class="p-3 bg-white rounded-md border border-gray-300 min-h-[12rem] whitespace-pre-wrap"
                                        >
                                            {{ resident.bathing_support }}
                                        </div>
                                    </div>
                                </div>

                                <!-- 移動や歩行に関する情報 -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        移動や歩行に関する情報
                                    </h3>
                                    <div class="mt-2">
                                        <div
                                            class="p-3 bg-white rounded-md border border-gray-300 min-h-[12rem] whitespace-pre-wrap"
                                        >
                                            {{ resident.mobility_support }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- その他の備考 -->
                            <div class="mt-6">
                                <h3 class="text-lg font-medium text-gray-900">
                                    その他の備考
                                </h3>
                                <div class="mt-2">
                                    <div
                                        class="p-3 bg-white rounded-md border border-gray-300 min-h-[12rem] whitespace-pre-wrap"
                                    >
                                        {{ resident.memo }}
                                    </div>
                                </div>
                            </div>

                            <!-- アクションボタン -->
                            <div class="mt-6 flex space-x-4">
                                <Link
                                    :href="route('residents.edit', resident.id)"
                                    class="bg-blue-100 text-blue-700 font-medium py-2 px-4 rounded-md transition hover:bg-blue-300 hover:text-white focus:outline-none focus:shadow-outline"
                                >
                                    編集する
                                </Link>
                                <Link
                                    :href="route('residents.index')"
                                    class="bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                                >
                                    一覧に戻る
                                </Link>
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
