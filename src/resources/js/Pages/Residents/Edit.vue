<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    resident: {
        type: Object,
        required: true,
    },
    units: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: props.resident.name,
    unit_id: props.resident.unit_id,
    meal_support: props.resident.meal_support,
    toilet_support: props.resident.toilet_support,
    bathing_support: props.resident.bathing_support,
    mobility_support: props.resident.mobility_support,
    memo: props.resident.memo,
});

const submit = () => {
    form.patch(route("residents.update", props.resident.id), {
        onSuccess: () => {
            // 成功時の処理（必要に応じて）
        },
    });
};
</script>

<template>
    <Head :title="`${resident.name}さんの情報編集`" />

    <AuthenticatedLayout>
        <div class="pt-6 pb-12 mt-16">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-100 dark:bg-gray-800 overflow-hidden rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-8 mb-6">
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                {{ resident.name }}さんの情報編集
                            </h2>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="space-y-6">
                                <!-- 2x2 グリッドレイアウト -->
                                <div class="grid grid-cols-2 gap-6">
                                    <!-- 利用者名 -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            利用者名
                                        </h3>
                                        <div class="mt-2">
                                            <input
                                                type="text"
                                                v-model="form.name"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            />
                                            <div
                                                v-if="form.errors.name"
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{ form.errors.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 所属ユニット -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            所属ユニット
                                        </h3>
                                        <div class="mt-2">
                                            <select
                                                v-model="form.unit_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            >
                                                <option
                                                    v-for="unit in units"
                                                    :key="unit.id"
                                                    :value="unit.id"
                                                >
                                                    {{ unit.name }}
                                                </option>
                                            </select>
                                            <div
                                                v-if="form.errors.unit_id"
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{ form.errors.unit_id }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 食事の支援 -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            食事の支援
                                        </h3>
                                        <div class="mt-2">
                                            <textarea
                                                v-model="form.meal_support"
                                                rows="8"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            ></textarea>
                                            <div
                                                v-if="form.errors.meal_support"
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{ form.errors.meal_support }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 排泄介助について -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            排泄介助について
                                        </h3>
                                        <div class="mt-2">
                                            <textarea
                                                v-model="form.toilet_support"
                                                rows="8"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            ></textarea>
                                            <div
                                                v-if="
                                                    form.errors.toilet_support
                                                "
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{ form.errors.toilet_support }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 入浴介助について -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            入浴介助について
                                        </h3>
                                        <div class="mt-2">
                                            <textarea
                                                v-model="form.bathing_support"
                                                rows="8"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            ></textarea>
                                            <div
                                                v-if="
                                                    form.errors.bathing_support
                                                "
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{
                                                    form.errors.bathing_support
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 移動や歩行に関する情報 -->
                                    <div>
                                        <h3
                                            class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            移動や歩行に関する情報
                                        </h3>
                                        <div class="mt-2">
                                            <textarea
                                                v-model="form.mobility_support"
                                                rows="8"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                            ></textarea>
                                            <div
                                                v-if="
                                                    form.errors.mobility_support
                                                "
                                                class="text-red-500 dark:text-red-400 text-sm mt-1"
                                            >
                                                {{
                                                    form.errors.mobility_support
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- その他の備考 -->
                                <div class="mt-6">
                                    <h3
                                        class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                    >
                                        その他の備考
                                    </h3>
                                    <div class="mt-2">
                                        <textarea
                                            v-model="form.memo"
                                            class="w-full p-3 bg-white dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600 min-h-[12rem] text-gray-900 dark:text-gray-100"
                                            :class="{
                                                'border-red-500 dark:border-red-400':
                                                    form.errors.memo,
                                            }"
                                        ></textarea>
                                        <div
                                            v-if="form.errors.memo"
                                            class="text-red-500 text-sm mt-1"
                                        >
                                            {{ form.errors.memo }}
                                        </div>
                                    </div>
                                </div>

                                <!-- アクションボタン -->
                                <div class="mt-6 flex space-x-4">
                                    <button
                                        type="submit"
                                        class="bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 font-medium py-2 px-4 rounded-md transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white focus:outline-none focus:shadow-outline"
                                        :disabled="form.processing"
                                    >
                                        保存する
                                    </button>
                                    <Link
                                        :href="
                                            route('residents.show', resident.id)
                                        "
                                        class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium py-2 px-4 rounded-md transition hover:bg-gray-500 dark:hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
                                    >
                                        キャンセル
                                    </Link>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
