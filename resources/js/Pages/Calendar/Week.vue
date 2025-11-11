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
    formInitialDate.value = dayjs(info.date).format('YYYY-MM-DD')
    formInitialResidentId.value = null
    showScheduleForm.value = true
}

// イベントクリック時の処理
const handleEventClick = (info) => {
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
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
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
const handleScheduleDeleted = () => {
    // カレンダーを再読み込み
    router.reload({ only: ['events'] })
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

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h1 class="text-2xl font-bold mb-6">週間カレンダー</h1>

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

<style>
.calendar-container {
    margin-top: 1rem;
}
</style>

