<script setup>
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import 'dayjs/locale/ja'
import jaLocale from '@fullcalendar/core/locales/ja.js'

dayjs.locale('ja')

const { t } = useI18n()
const { props } = usePage()

const events = ref(props.events || [])
const residents = ref(props.residents || [])
const scheduleTypes = ref(props.scheduleTypes || [])
const monthStats = ref(props.monthStats || { total: 0, by_type: {} })
const currentDate = ref(props.currentDate || dayjs().format('YYYY-MM-DD'))

// FullCalendarの設定
const calendarOptions = ref({
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
    eventDisplay: 'block',
    height: 'auto',
    editable: false, // M3-1では編集不可
    selectable: false, // M3-1では選択不可
    dayMaxEvents: true,
    moreLinkClick: 'popover',
})

// カレンダーの日付変更時の処理
const handleDateChange = (info) => {
    // 月が変更された場合、新しい月のデータを取得
    const newDate = dayjs(info.start).format('YYYY-MM-DD')
    const monthStart = dayjs(currentDate.value).startOfMonth().format('YYYY-MM-DD')
    const newMonthStart = dayjs(info.start).startOfMonth().format('YYYY-MM-DD')
    
    if (newMonthStart !== monthStart) {
        // Inertiaでページを再読み込み
        router.get(route('calendar.index'), { date: newDate }, {
            preserveState: true,
            preserveScroll: true,
        })
    }
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
                            <FullCalendar :options="calendarOptions" @datesSet="handleDateChange" />
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
    </AuthenticatedLayout>
</template>

<style>
@import '@fullcalendar/core/index.css';
@import '@fullcalendar/daygrid/index.css';

.calendar-container {
    margin-top: 1rem;
}
</style>

