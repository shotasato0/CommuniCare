<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from "vue";

const props = defineProps({
    resident: {
        type: Object,
        required: true,
    },
});

const residentData = ref({ ...props.resident });
</script>

<template>
    <Head :title="`${resident.name}さんの詳細情報`" />

    <AuthenticatedLayout>
        <div class="pt-6 pb-12 mt-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    {{ resident.name }}さんの情報
                </h2>

                <div class="bg-gray-100 p-6 space-y-6 rounded-lg">
                    <!-- 2x2 グリッドレイアウト -->
                    <div class="grid grid-cols-2 gap-6">
                        <!-- 食事の支援 -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                食事の支援
                            </h3>
                            <div class="mt-2">
                                <textarea
                                    v-model="residentData.meal_support"
                                    rows="8"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- 排泄介助について -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                排泄介助について
                            </h3>
                            <div class="mt-2">
                                <textarea
                                    v-model="residentData.toilet_support"
                                    rows="8"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- 入浴介助について -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                入浴介助について
                            </h3>
                            <div class="mt-2">
                                <textarea
                                    v-model="residentData.bathing_support"
                                    rows="8"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- 移動や歩行に関する情報 -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                移動や歩行に関する情報
                            </h3>
                            <div class="mt-2">
                                <textarea
                                    v-model="residentData.mobility_support"
                                    rows="8"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- その他の備考 (1行で表示) -->
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            その他の備考
                        </h3>
                        <div class="mt-2">
                            <textarea
                                v-model="residentData.memo"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- アクションボタン -->
                    <div class="mt-6 flex space-x-4">
                        <Link
                            :href="route('residents.edit', resident.id)"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            編集する
                        </Link>
                        <Link
                            :href="route('residents.index')"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            一覧に戻る
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
