<script setup>
import { Head } from "@inertiajs/vue3";
import { useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, watchEffect } from "vue";
import CustomDialog from "@/Components/CustomDialog.vue";
import { useDialog } from "@/composables/dialog";

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

const dialog = useDialog();

// 部署削除機能
const deleteUnit = async (unit) => {
    const result = await dialog.showDialog(
        `${unit.name}を削除してもよろしいですか？`
    );
    if (!result) {
        return;
    }

    form.delete(route("units.destroy", unit.id), {
        onSuccess: () => {
            // 成功した場合にローカルステートから部署を削除
            const index = units.findIndex((unit) => unit.id === unit.id);
            if (index !== -1) {
                units.splice(index, 1);
            }
            flashMessage.value = "部署が削除されました。";
        },
    });
};

// flashMessageの変更を監視して非表示タイマーを設定
watchEffect(() => {
    if (flashMessage.value) {
        const timeout = setTimeout(() => {
            flashMessage.value = null;
        }, 8000);

        // クリーンアップでタイマーをクリア
        return () => clearTimeout(timeout);
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <!-- カスタムダイアログ -->
        <CustomDialog
            :is-visible="dialog.state.isVisible"
            :message="dialog.state.message"
            @confirm="dialog.confirm"
            @cancel="dialog.cancel"
        />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $t("Unit Management") }}
            </h2>
        </template>
        <Head :title="$t('Unit Management')" />
        <div
            class="max-w-2xl mx-auto pb-12 px-4 sm:px-6 lg:px-8 space-y-8 mt-16"
        >
            <!-- フラッシュメッセージ -->
            <transition name="fade">
                <div
                    v-if="flashMessage"
                    class="fixed bottom-10 left-1/2 transform -translate-x-1/2 w-full max-w-md bg-green-100 dark:bg-green-800 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-200 p-4 rounded shadow-lg z-50"
                >
                    <p class="font-bold text-center">{{ flashMessage }}</p>
                </div>
            </transition>

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $t("Unit Registration") }}
            </h2>
            <!-- 部署登録フォーム -->
            <form
                @submit.prevent="submit"
                class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow"
            >
                <div class="mb-4">
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >{{ $t("Unit Name") }}</label
                    >
                    <input
                        type="text"
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <div
                        v-if="form.errors.name"
                        class="text-red-600 dark:text-red-400 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full sm:w-auto px-4 py-2 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-md transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white text-center"
                >
                    {{ $t("Register Unit") }}
                </button>
            </form>

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $t("Unit List") }}
            </h2>
            <!-- 部署一覧 -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <ul class="divide-y divide-gray-200 dark:divide-gray-600">
                    <li
                        v-for="unit in units"
                        :key="unit.id"
                        class="flex justify-between items-center py-4"
                    >
                        <span class="text-gray-800 dark:text-gray-200">{{ unit.name }}</span>
                        <button
                            @click="deleteUnit(unit)"
                            class="px-4 py-2 bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 rounded-md transition hover:bg-red-300 dark:hover:bg-red-600 hover:text-white text-center"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </li>
                </ul>

                <!-- 部署がない場合のメッセージ -->
                <p v-if="units.length === 0" class="text-gray-500 dark:text-gray-400 mt-4">
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
