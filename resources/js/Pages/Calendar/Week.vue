<script setup>
import { ref, computed, watch } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import dayjs from 'dayjs'
import 'dayjs/locale/ja'
import jaLocale from '@fullcalendar/core/locales/ja.js'
import ScheduleForm from '@/Components/ScheduleForm.vue'
import ScheduleModal from '@/Components/ScheduleModal.vue'

dayjs.locale('ja')

const { props } = usePage()

const events = ref(props.events || [])
const residents = ref(props.residents || [])
const scheduleTypes = ref(props.scheduleTypes || [])
const currentDate = ref(props.currentDate || dayjs().format('YYYY-MM-DD'))
const startOfWeek = ref(props.startOfWeek || dayjs().startOf('week').add(1, 'day').format('YYYY-MM-DD')) // 月曜日始まり
const endOfWeek = ref(props.endOfWeek || dayjs().endOf('week').add(1, 'day').format('YYYY-MM-DD'))

// FullCalendarのref
const calendarRef = ref(null)

// propsの変更を監視してrefを更新
watch(
    () => props.events,
    (newEvents) => {
        if (newEvents) {
            events.value = newEvents
            // FullCalendarのイベントを更新
            if (calendarRef.value) {
                const calendarApi = calendarRef.value.getApi()
                calendarApi.refetchEvents()
            }
        }
    },
    { deep: true }
)

// スケジュール作成フォームの表示状態
const showScheduleForm = ref(false)
const formInitialDate = ref(null)
const formInitialResidentId = ref(null)

// スケジュール詳細モーダルの表示状態
const showScheduleModal = ref(false)
const selectedSchedule = ref(null)

// カレンダーの週変更時の処理
const handleWeekChange = (info) => {
    // 週が変更された場合、新しい週のデータを取得
    const newDate = dayjs(info.start).format('YYYY-MM-DD')
    const weekStart = dayjs(startOfWeek.value).format('YYYY-MM-DD')
    const newWeekStart = dayjs(info.start).startOf('week').add(1, 'day').format('YYYY-MM-DD') // 月曜日始まり
    
    if (newWeekStart !== weekStart) {
        // Inertiaでページを再読み込み
        router.get(route('calendar.week'), { date: newDate }, {
            preserveState: true,
            preserveScroll: true,
        })
    }
}

// 日付クリック時の処理
const handleDateClick = (info) => {
    // イベントがクリックされた場合は何もしない（eventClickで処理される）
    const clickedElement = info.jsEvent?.target;
    if (clickedElement) {
        // イベント要素がクリックされた場合は無視
        const eventElement = clickedElement.closest('.fc-event');
        if (eventElement) {
            return; // イベントクリックで処理されるため、dateClickは無視
        }
    }
    
    formInitialDate.value = dayjs(info.date).format('YYYY-MM-DD')
    formInitialResidentId.value = null
    showScheduleForm.value = true
}

// イベントクリック時の処理
const handleEventClick = (info) => {
    // スケジュール作成フォームを閉じる（誤って開かないように）
    showScheduleForm.value = false
    
    selectedSchedule.value = info.event
    showScheduleModal.value = true
}

// FullCalendarの設定
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    locale: jaLocale,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'customMonth,timeGridWeek,customDay',
    },
    customButtons: {
        customMonth: {
            text: '月',
            click: () => {
                router.get(route('calendar.index'), { date: currentDate.value });
            }
        },
        customDay: {
            text: '日',
            click: () => {
                router.get(route('calendar.day'), { date: currentDate.value });
            }
        }
    },
    buttonText: {
        today: '今日',
        month: '月',
        week: '週',
        day: '日',
    },
    events: events.value,
    eventDisplay: 'block',
    height: 'auto',
    editable: false, // 編集はモーダルから行う
    selectable: false, // 範囲選択は無効 - 日付クリックで作成フォームを表示
    dateClick: handleDateClick, // 日付クリック時の処理
    eventClick: handleEventClick, // イベントクリック時の処理
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    slotDuration: '00:30:00',
    firstDay: 1, // 月曜日始まり
    allDaySlot: false, // 終日スロットを非表示（必要に応じて有効化）
    dayHeaderFormat: { weekday: 'short', month: 'numeric', day: 'numeric', omitCommas: true },
}))

// スケジュール作成成功時の処理
const handleScheduleCreated = (newEvent) => {
    console.log('handleScheduleCreated called (Week)', newEvent);
    
    if (newEvent) {
        // 作成されたイベントを直接eventsに追加
        events.value = [...events.value, newEvent];
        console.log('Added new event to events.value:', events.value.length);
        
        // FullCalendarのイベントを更新
        if (calendarRef.value) {
            const calendarApi = calendarRef.value.getApi();
            calendarApi.refetchEvents();
        }
    }
}

// フォームを閉じる
const closeScheduleForm = () => {
    showScheduleForm.value = false
    formInitialDate.value = null
    formInitialResidentId.value = null
}

// スケジュール更新成功時の処理
const handleScheduleUpdated = () => {
    // カレンダーを再読み込み
    router.reload({ only: ['events'] })
}

// スケジュール削除成功時の処理
const handleScheduleDeleted = (deletedScheduleId) => {
    console.log('handleScheduleDeleted called (Week)', deletedScheduleId);
    
    // まずモーダルを閉じる（確実に閉じるため）
    closeScheduleModal();
    
    if (deletedScheduleId) {
        // 削除されたスケジュールをeventsから直接削除
        events.value = events.value.filter(event => event.id !== deletedScheduleId);
        console.log('Removed deleted event from events.value:', events.value.length);
        
        // FullCalendarのイベントを更新
        if (calendarRef.value) {
            const calendarApi = calendarRef.value.getApi();
            calendarApi.refetchEvents();
        }
    }
}

// モーダルを閉じる
const closeScheduleModal = () => {
    showScheduleModal.value = false
    selectedSchedule.value = null
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="週間カレンダー" />

        <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- ヘッダーエリア -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">週間カレンダー</h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">週ごとのスケジュールを確認できます</p>
                    </div>
                </div>

                <!-- カレンダーメインエリア -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <!-- カレンダー表示 -->
                        <div class="calendar-container">
                            <FullCalendar ref="calendarRef" :options="calendarOptions" @datesSet="handleWeekChange" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- スケジュール作成フォーム -->
        <ScheduleForm
            :show="showScheduleForm"
            :initial-date="formInitialDate"
            :initial-resident-id="formInitialResidentId"
            :residents="residents"
            :schedule-types="scheduleTypes"
            @close="closeScheduleForm"
            @success="handleScheduleCreated"
        />

        <!-- スケジュール詳細モーダル -->
        <ScheduleModal
            :show="showScheduleModal"
            :schedule="selectedSchedule"
            :residents="residents"
            :schedule-types="scheduleTypes"
            @close="closeScheduleModal"
            @updated="handleScheduleUpdated"
            @deleted="handleScheduleDeleted"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
/* カレンダーコンテナ */
.calendar-container {
    font-family: 'Inter', sans-serif;
}

/* FullCalendarの全体スタイル調整 */
:deep(.fc) {
    --fc-border-color: #f3f4f6;
    --fc-today-bg-color: rgba(59, 130, 246, 0.05);
    --fc-neutral-bg-color: #f9fafb;
    --fc-page-bg-color: #ffffff;
}

.dark :deep(.fc) {
    --fc-border-color: #374151;
    --fc-neutral-bg-color: #1f2937;
    --fc-page-bg-color: #1f2937;
}

/* ヘッダーツールバー */
:deep(.fc-header-toolbar) {
    margin-bottom: 1.5rem !important;
}

:deep(.fc-toolbar-title) {
    font-size: 1.5rem !important;
    font-weight: 700;
    color: #1f2937;
}

.dark :deep(.fc-toolbar-title) {
    color: #f3f4f6;
}

/* ボタンのカスタマイズ */
:deep(.fc-button) {
    background-color: white;
    border: 1px solid #e5e7eb;
    color: #374151;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: all 0.2s;
}

:deep(.fc-button:hover) {
    background-color: #f9fafb;
    border-color: #d1d5db;
    color: #111827;
}

:deep(.fc-button-primary:not(:disabled).fc-button-active),
:deep(.fc-button-primary:not(:disabled):active) {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.dark :deep(.fc-button) {
    background-color: #374151;
    border-color: #4b5563;
    color: #e5e7eb;
}

.dark :deep(.fc-button:hover) {
    background-color: #4b5563;
}

/* 曜日ヘッダー */
:deep(.fc-col-header-cell) {
    padding: 0.75rem 0;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

:deep(.fc-col-header-cell-cushion) {
    color: #6b7280;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.875rem;
}

.dark :deep(.fc-col-header-cell) {
    background-color: #1f2937;
    border-bottom-color: #374151;
}

.dark :deep(.fc-col-header-cell-cushion) {
    color: #9ca3af;
}

/* タイムグリッドのスロット */
:deep(.fc-timegrid-slot) {
    height: 3rem; /* スロットの高さを少し広げる */
}

:deep(.fc-timegrid-slot-label) {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

/* イベントスタイル（TimeGrid用） */
:deep(.fc-timegrid-event) {
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: none;
    padding: 2px;
    transition: transform 0.1s;
}

:deep(.fc-timegrid-event:hover) {
    transform: scale(1.02);
    z-index: 5;
}

:deep(.fc-event-main) {
    padding: 2px 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* スクロールバーのカスタマイズ */
:deep(::-webkit-scrollbar) {
    width: 6px;
    height: 6px;
}

:deep(::-webkit-scrollbar-track) {
    background: transparent;
}

:deep(::-webkit-scrollbar-thumb) {
    background: #d1d5db;
    border-radius: 3px;
}

:deep(::-webkit-scrollbar-thumb:hover) {
    background: #9ca3af;
}

.dark :deep(::-webkit-scrollbar-thumb) {
    background: #4b5563;
}
</style>

