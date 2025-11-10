<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "./Modal.vue";
import dayjs from "dayjs";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialDate: {
        type: String,
        default: null,
    },
    initialResidentId: {
        type: Number,
        default: null,
    },
    residents: {
        type: Array,
        default: () => [],
    },
    scheduleTypes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "success"]);

const form = useForm({
    date: props.initialDate || dayjs().format("YYYY-MM-DD"),
    resident_id: props.initialResidentId || null,
    schedule_name: "",
    start_time: "09:00",
    end_time: "10:00",
    memo: "",
});

// propsの変更を監視してフォームを更新
watch(
    () => props.initialDate,
    (newDate) => {
        if (newDate) {
            form.date = newDate;
        }
    }
);

watch(
    () => props.initialResidentId,
    (newResidentId) => {
        if (newResidentId) {
            form.resident_id = newResidentId;
        }
    }
);

// モーダルが閉じられたときにフォームをリセット
watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
    }
);

const submit = () => {
    form.post(route("calendar.schedule.store"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("success");
            emit("close");
            form.reset();
        },
        onError: () => {
            // エラーはform.errorsに自動的に設定される
        },
    });
};

const close = () => {
    emit("close");
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2
                    class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                >
                    スケジュール作成
                </h2>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <form @submit.prevent="submit">
                <!-- 日付 -->
                <div class="mb-4">
                    <label
                        for="date"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        日付 <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="date"
                        v-model="form.date"
                        type="date"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.date }"
                    />
                    <div
                        v-if="form.errors.date"
                        class="mt-1 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.date }}
                    </div>
                </div>

                <!-- スケジュール名 -->
                <div class="mb-4">
                    <label
                        for="schedule_name"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        スケジュール名 <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="schedule_name"
                        v-model="form.schedule_name"
                        type="text"
                        placeholder="スケジュール名を入力してください"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.schedule_name }"
                    />
                    <div
                        v-if="form.errors.schedule_name"
                        class="mt-1 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.schedule_name }}
                    </div>
                </div>

                <!-- 開始時刻・終了時刻 -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label
                            for="start_time"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            開始時刻 <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="start_time"
                            v-model="form.start_time"
                            type="time"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{
                                'border-red-500': form.errors.start_time,
                            }"
                        />
                        <div
                            v-if="form.errors.start_time"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.start_time }}
                        </div>
                    </div>
                    <div>
                        <label
                            for="end_time"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            終了時刻 <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="end_time"
                            v-model="form.end_time"
                            type="time"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': form.errors.end_time }"
                        />
                        <div
                            v-if="form.errors.end_time"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.end_time }}
                        </div>
                    </div>
                </div>

                <!-- メモ -->
                <div class="mb-4">
                    <label
                        for="memo"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        メモ
                    </label>
                    <textarea
                        id="memo"
                        v-model="form.memo"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        :class="{ 'border-red-500': form.errors.memo }"
                    ></textarea>
                    <div
                        v-if="form.errors.memo"
                        class="mt-1 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.memo }}
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                    >
                        キャンセル
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? "作成中..." : "作成" }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
