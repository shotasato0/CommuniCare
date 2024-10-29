<script setup>
import { Head } from "@inertiajs/vue3";
import { useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineProps({
    units: Array,
});

const form = useForm({
    name: "",
});

const submit = () => {
    form.post(route("units.store"));
};

// 現在の部署一覧を取得
const { units = [] } = usePage().props;
console.log("units", units);

// 部署削除機能
const deleteUnit = (id) => {
    if (confirm("本当に削除しますか？")) {
        form.delete(route("units.destroy", id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="$t('Unit Registration')" />
        <div class="max-w-2xl mx-auto py-10 space-y-8 mt-16">
            <!-- 部署登録フォーム -->
            <form
                @submit.prevent="submit"
                class="bg-white p-6 rounded-lg shadow"
            >
                <div class="mb-4">
                    <h2 class="text-xl font-bold mb-6">
                        {{ $t("Unit Registration") }}
                    </h2>
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

            <!-- 部署一覧 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">{{ $t("Unit List") }}</h2>

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
