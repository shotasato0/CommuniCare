<script setup>
import { ref, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import Modal from './Modal.vue'
import dayjs from 'dayjs'
import axios from 'axios'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    schedule: {
        type: Object,
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
})

const emit = defineEmits(['close', 'updated', 'deleted'])

const isEditing = ref(false)
const deleteError = ref('')
const isDeleting = ref(false)

const form = useForm({
    date: '',
    resident_id: null,
    schedule_type_id: null,
    start_time: '',
    end_time: '',
    memo: '',
})

// スケジュールデータをフォームに設定
const initializeForm = () => {
    if (props.schedule) {
        const scheduleDate = dayjs(props.schedule.start).format('YYYY-MM-DD')
        form.date = scheduleDate
        form.resident_id = props.schedule.extendedProps?.resident_id || null
        form.schedule_type_id = props.schedule.extendedProps?.schedule_type_id || null
        form.start_time = dayjs(props.schedule.start).format('HH:mm')
        form.end_time = dayjs(props.schedule.end).format('HH:mm')
        form.memo = props.schedule.extendedProps?.memo || ''
    }
}

// モーダルが開かれたときにフォームを初期化
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.schedule) {
            initializeForm()
            isEditing.value = false
            deleteError.value = ''
        } else {
            form.reset()
            form.clearErrors()
            deleteError.value = ''
        }
    }
)

const updateSchedule = () => {
    if (!props.schedule) return

    form.put(route('calendar.schedule.update', props.schedule.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated')
            emit('close')
            isEditing.value = false
            form.reset()
        },
    })
}

const deleteSchedule = async () => {
    if (!props.schedule) return

    if (!confirm('このスケジュールを削除してもよろしいですか？')) {
        return
    }

    deleteError.value = ''
    isDeleting.value = true

    try {
        await axios.delete(route('calendar.schedule.destroy', props.schedule.id), {
            headers: {
                'X-Inertia': 'true',
                Accept: 'application/json',
            },
        })

        // 削除されたスケジュールIDを親コンポーネントに渡す
        // モーダルの閉じる処理は親コンポーネントのhandleScheduleDeleted内で行う
        emit('deleted', props.schedule.id)
    } catch (error) {
        console.error('ScheduleModal delete error:', error)
        
        // エラーメッセージを取得
        const errorMessage =
            error.response?.data?.message ||
            'スケジュールの削除に失敗しました。'
        
        deleteError.value = errorMessage
        
        // エラーメッセージを3秒後にクリア
        setTimeout(() => {
            deleteError.value = ''
        }, 5000)
    } finally {
        isDeleting.value = false
    }
}

const close = () => {
    emit('close')
    isEditing.value = false
    form.reset()
    form.clearErrors()
    deleteError.value = ''
}

const startEdit = () => {
    isEditing.value = true
}

const cancelEdit = () => {
    isEditing.value = false
    initializeForm()
    form.clearErrors()
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ isEditing ? 'スケジュール編集' : 'スケジュール詳細' }}
                </h2>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <div v-if="schedule">
                <!-- 編集モード -->
                <form v-if="isEditing" @submit.prevent="updateSchedule">
                    <!-- 日付 -->
                    <div class="mb-4">
                        <label
                            for="edit_date"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            日付 <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="edit_date"
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

                    <!-- 利用者 -->
                    <div class="mb-4">
                        <label
                            for="edit_resident_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            利用者 <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="edit_resident_id"
                            v-model="form.resident_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': form.errors.resident_id }"
                        >
                            <option value="">選択してください</option>
                            <option
                                v-for="resident in residents"
                                :key="resident.id"
                                :value="resident.id"
                            >
                                {{ resident.name }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.resident_id"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.resident_id }}
                        </div>
                    </div>

                    <!-- スケジュール種別 -->
                    <div class="mb-4">
                        <label
                            for="edit_schedule_type_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            スケジュール種別 <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="edit_schedule_type_id"
                            v-model="form.schedule_type_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-500': form.errors.schedule_type_id }"
                        >
                            <option value="">選択してください</option>
                            <option
                                v-for="type in scheduleTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{ type.name }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.schedule_type_id"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.schedule_type_id }}
                        </div>
                    </div>

                    <!-- 開始時刻・終了時刻 -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label
                                for="edit_start_time"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                開始時刻 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="edit_start_time"
                                v-model="form.start_time"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.start_time }"
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
                                for="edit_end_time"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                終了時刻 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="edit_end_time"
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
                            for="edit_memo"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            メモ
                        </label>
                        <textarea
                            id="edit_memo"
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
                            @click="cancelEdit"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            キャンセル
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ form.processing ? '更新中...' : '更新' }}
                        </button>
                    </div>
                </form>

                <!-- 表示モード -->
                <div v-else>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                日付
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ dayjs(schedule.start).format('YYYY年MM月DD日') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                スケジュール名
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ schedule.extendedProps?.schedule_type_name || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                スケジュール種別
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ schedule.extendedProps?.schedule_type_name || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                時間
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ dayjs(schedule.start).format('HH:mm') }} -
                                {{ dayjs(schedule.end).format('HH:mm') }}
                            </dd>
                        </div>
                        <div v-if="schedule.extendedProps?.memo">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                メモ
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                {{ schedule.extendedProps.memo }}
                            </dd>
                        </div>
                    </dl>

                    <!-- エラーメッセージ -->
                    <div
                        v-if="deleteError"
                        class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md"
                    >
                        <p class="text-sm text-red-600 dark:text-red-400">
                            {{ deleteError }}
                        </p>
                    </div>

                    <!-- ボタン -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            @click="deleteSchedule"
                            :disabled="isDeleting"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isDeleting ? '削除中...' : '削除' }}
                        </button>
                        <button
                            @click="startEdit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            編集
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

