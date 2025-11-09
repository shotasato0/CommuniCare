<script setup>
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
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
const monthStats = ref(props.monthStats || { total: 0, by_type: {} })
const currentDate = ref(props.currentDate || dayjs().format('YYYY-MM-DD'))

// デバッグ: 初期データを確認
console.log('Initial props:', props)
console.log('Initial events:', events.value)
console.log('Initial residents:', residents.value)

// スケジュール作成フォームの表示状態
const showScheduleForm = ref(false)
const formInitialDate = ref(null)
const formInitialResidentId = ref(null)

// スケジュール詳細モーダルの表示状態
const showScheduleModal = ref(false)
const selectedSchedule = ref(null)

// カレンダーの月変更時の処理
const handleMonthChange = (info) => {
    // 月が変更された場合、新しい月のデータを取得
    const newDate = dayjs(info.start).format('YYYY-MM-DD')
    const monthStart = dayjs(currentDate.value).startOf('month').format('YYYY-MM-DD')
    const newMonthStart = dayjs(info.start).startOf('month').format('YYYY-MM-DD')
    
    if (newMonthStart !== monthStart) {
        // Inertiaでページを再読み込み
        router.get(route('calendar.index'), { date: newDate }, {
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

// 日付ごとにスケジュールと入浴予定者をグループ化
const eventsByDate = computed(() => {
    const grouped = {}
    console.log('eventsByDate computed - events.value:', events.value)
    console.log('eventsByDate computed - events.value.length:', events.value?.length)
    
    if (!events.value || events.value.length === 0) {
        console.warn('events.value is empty or undefined')
        return grouped
    }
    
    events.value.forEach((event, index) => {
        console.log(`Processing event ${index}:`, event)
        if (!event.start) {
            console.warn(`Event ${index} has no start date:`, event)
            return
        }
        const date = dayjs(event.start).format('YYYY-MM-DD')
        console.log(`Event ${index} date:`, date)
        if (!grouped[date]) {
            grouped[date] = {
                schedules: [],
                residents: new Set()
            }
        }
        grouped[date].schedules.push(event)
        if (event.extendedProps?.resident_id) {
            grouped[date].residents.add(event.extendedProps.resident_id)
        }
    })
    console.log('eventsByDate computed - grouped:', grouped)
    return grouped
})

// 日付セルのカスタムコンテンツ
const dayCellContent = (info) => {
    const dateStr = dayjs(info.date).format('YYYY-MM-DD')
    const dayData = eventsByDate.value[dateStr]
    
    console.log('dayCellContent called for date:', dateStr, 'dayData:', dayData)
    
    // スケジュールセクション
    const schedulesHtml = dayData && dayData.schedules.length > 0
        ? dayData.schedules.map(schedule => {
            const time = dayjs(schedule.start).format('HH:mm')
            const title = schedule.extendedProps?.schedule_type_name || schedule.title
            const color = schedule.backgroundColor || '#3B82F6'
            return `<div class="calendar-schedule-item" style="background-color: ${color};" data-event-id="${schedule.id}">
                <span class="schedule-time">${time}</span>
                <span class="schedule-title">${title}</span>
            </div>`
        }).join('')
        : ''
    
    // 入浴予定者セクション
    const residentsList = dayData && dayData.residents.size > 0
        ? Array.from(dayData.residents).map(residentId => {
            const resident = residents.value.find(r => r.id === residentId)
            return resident ? resident.name : ''
        }).filter(Boolean)
        : []
    
    const residentsHtml = residentsList.length > 0 
        ? `<div class="calendar-residents-section">
            <div class="residents-label">入浴予定者</div>
            <div class="residents-list">${residentsList.join(', ')}</div>
        </div>`
        : ''
    
    const html = `<div class="custom-day-cell">
        <div class="day-number">${info.dayNumberText}</div>
        <div class="day-content">
            <div class="schedules-section">${schedulesHtml}</div>
            ${residentsHtml}
        </div>
    </div>`
    
    console.log('dayCellContent returning html for', dateStr, ':', html.substring(0, 100))
    
    // FullCalendar v6では、stringまたは{ html: string }形式を返すことができます
    return html
}

// 日付セルがマウントされた後にクリックイベントをバインド
const dayCellDidMount = (info) => {
    // スケジュールアイテムのクリックイベントをバインド
    const scheduleItems = info.el.querySelectorAll('.calendar-schedule-item')
    scheduleItems.forEach(item => {
        const eventId = item.getAttribute('data-event-id')
        if (eventId) {
            item.addEventListener('click', (e) => {
                e.stopPropagation()
                const event = events.value.find(ev => ev.id === eventId)
                if (event) {
                    // FullCalendarのEventオブジェクト形式に変換
                    const fcEvent = {
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        end: event.end,
                        backgroundColor: event.backgroundColor,
                        borderColor: event.borderColor,
                        extendedProps: event.extendedProps,
                    }
                    handleEventClick({ event: fcEvent })
                }
            })
        }
    })
}

// FullCalendarの設定
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: jaLocale,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    buttonText: {
        today: '今日',
        month: '月',
        week: '週',
        day: '日',
    },
    events: events.value,
    eventDisplay: 'none', // カスタム表示のため標準イベント表示を無効化
    height: 'auto',
    editable: false,
    selectable: false,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    dayCellContent: dayCellContent, // カスタム日付セルコンテンツ
    dayCellDidMount: dayCellDidMount, // 日付セルマウント後の処理
    dayMaxEvents: false, // カスタム表示のため無効化
}))

// スケジュール作成成功時の処理
const handleScheduleCreated = () => {
    // カレンダーを再読み込み
    router.reload({ only: ['events', 'monthStats'] })
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
    router.reload({ only: ['events', 'monthStats'] })
}

// スケジュール削除成功時の処理
const handleScheduleDeleted = () => {
    // カレンダーを再読み込み
    router.reload({ only: ['events', 'monthStats'] })
}

// モーダルを閉じる
const closeScheduleModal = () => {
    showScheduleModal.value = false
    selectedSchedule.value = null
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="カレンダー" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h1 class="text-2xl font-bold mb-6">カレンダー</h1>

                        <!-- カレンダー表示 -->
                        <div class="calendar-container">
                            <FullCalendar :options="calendarOptions" @datesSet="handleMonthChange" />
                        </div>

                        <!-- 月間統計情報 -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="text-sm text-gray-600 dark:text-gray-400">総スケジュール数</div>
                                <div class="text-2xl font-bold">{{ monthStats.total }}</div>
                            </div>
                            <div
                                v-for="type in scheduleTypes"
                                :key="type.id"
                                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg"
                            >
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ type.name }}</div>
                                <div class="text-2xl font-bold">{{ monthStats.by_type[type.id] || 0 }}</div>
                            </div>
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

/* カレンダー全体を大きくする */
:deep(.fc) {
    font-size: 1.1rem;
}

:deep(.fc-daygrid-day-frame) {
    min-height: 150px;
    height: auto;
}

:deep(.fc-daygrid-day) {
    height: auto;
    min-height: 150px;
}

/* カスタム日付セル */
:deep(.custom-day-cell) {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 150px;
}

:deep(.day-number) {
    font-weight: bold;
    font-size: 1.1rem;
    padding: 4px 8px;
    border-bottom: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

:deep(.day-content) {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 4px;
    gap: 4px;
}

/* スケジュールセクション */
:deep(.schedules-section) {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow-y: auto;
    min-height: 60px;
    max-height: 80px;
}

:deep(.calendar-schedule-item) {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.85rem;
    color: white;
    cursor: pointer;
    transition: opacity 0.2s;
}

:deep(.calendar-schedule-item:hover) {
    opacity: 0.8;
}

:deep(.schedule-time) {
    font-weight: bold;
    white-space: nowrap;
}

:deep(.schedule-title) {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 入浴予定者セクション */
:deep(.calendar-residents-section) {
    border-top: 2px solid #e5e7eb;
    padding-top: 4px;
    margin-top: 4px;
    min-height: 50px;
}

:deep(.residents-label) {
    font-size: 0.75rem;
    font-weight: bold;
    color: #6b7280;
    margin-bottom: 2px;
}

:deep(.residents-list) {
    font-size: 0.85rem;
    color: #374151;
    line-height: 1.4;
    word-break: break-word;
}

/* 今日の日付を強調 */
:deep(.fc-day-today .day-number) {
    background-color: #3b82f6;
    color: white;
    border-radius: 4px;
}

/* ダークモード対応 */
.dark :deep(.day-number) {
    background-color: #374151;
    color: #f9fafb;
    border-bottom-color: #4b5563;
}

.dark :deep(.day-content) {
    background-color: #1f2937;
}

.dark :deep(.calendar-residents-section) {
    border-top-color: #4b5563;
}

.dark :deep(.residents-label) {
    color: #9ca3af;
}

.dark :deep(.residents-list) {
    color: #e5e7eb;
}

.dark :deep(.fc-day-today .day-number) {
    background-color: #2563eb;
    color: white;
}
</style>

