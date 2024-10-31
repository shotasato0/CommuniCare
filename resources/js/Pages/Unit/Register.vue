<script setup>
import { Head } from "@inertiajs/vue3";
import { useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, watchEffect } from "vue";

const { props } = usePage();
const flashMessage = ref(props.flash.success || null);

defineProps({
    units: Array,
});

const form = useForm({
    name: "",
});

const submit = () => {
    form.post(route("units.store"), {
        onSuccess: () => {
            flashMessage.value = "部署が正常に登録されました。";
        },
    });
};

// 現在の部署一覧を取得
const { units = [] } = usePage().props;
console.log("units", units);

// 部署削除機能
const deleteUnit = (id) => {
    if (confirm("本当に削除しますか？")) {
        form.delete(route("units.destroy", id), {
            onSuccess: () => {
                // 成功した場合にローカルステートから部署を削除
                const index = units.findIndex((unit) => unit.id === id);
                if (index !== -1) {
                    units.splice(index, 1);
                }
                flashMessage.value = "部署が削除されました。";
            },
        });
    }
};

// flashMessageの変更を監視して非表示タイマーを設定
watchEffect(() => {
    if (flashMessage.value) {
        const timeout = setTimeout(() => {
            flashMessage.value = null;
        }, 3000);

        // クリーンアップでタイマーをクリア
        return () => clearTimeout(timeout);
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="$t('Unit Registration')" />
        <div
            class="max-w-2xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8 mt-16"
        >
            <!-- フラッシュメッセージ -->
            <transition name="fade">
                <div
                    v-if="flashMessage"
                    class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 text-green-700 p-4 rounded shadow text-center"
                >
                    {{ flashMessage }}
                </div>
            </transition>

            <h1 class="text-2xl font-bold mb-6">
                {{ $t("Unit Registration") }}
            </h1>
            <!-- 部署登録フォーム -->
            <form
                @submit.prevent="submit"
                class="bg-white p-6 rounded-lg shadow"
            >
                <div class="mb-4">
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700"
                        >{{ $t("Unit Name") }}</label
                    >
                    <input
                        type="text"
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <div
                        v-if="form.errors.name"
                        class="text-red-600 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </div>
                </div>

                <button
                    type="submit"
                    class="bg-blue-500 link-hover text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    {{ $t("Register Unit") }}
                </button>
            </form>

            <h1 class="text-2xl font-bold mb-4">{{ $t("Unit List") }}</h1>
            <!-- 部署一覧 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <ul class="divide-y divide-gray-200">
                    <li
                        v-for="unit in units"
                        :key="unit.id"
                        class="flex justify-between items-center py-4"
                    >
                        <span class="text-gray-800">{{ unit.name }}</span>
                        <button
                            @click="deleteUnit(unit.id)"
                            class="px-2 py-1 ml-2 rounded bg-red-500 text-white font-bold link-hover cursor-pointer"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </li>
                </ul>

                <!-- 部署がない場合のメッセージ -->
                <p v-if="units.length === 0" class="text-gray-500 mt-4">
                    {{ $t("No units available.") }}
                </p>
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
