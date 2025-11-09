<script setup>
import { ref, computed } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import dayjs from "dayjs";
import "dayjs/locale/ja";
import jaLocale from "@fullcalendar/core/locales/ja.js";
import ScheduleForm from "@/Components/ScheduleForm.vue";
import ScheduleModal from "@/Components/ScheduleModal.vue";

dayjs.locale("ja");

const { props } = usePage();

const events = ref(props.events || []);
const residents = ref(props.residents || []);
const scheduleTypes = ref(props.scheduleTypes || []);
const monthStats = ref(props.monthStats || { total: 0, by_type: {} });
const currentDate = ref(props.currentDate || dayjs().format("YYYY-MM-DD"));

// デバッグ: 初期データを確認
console.log("Initial props:", props);
console.log("Initial events:", events.value);
console.log("Initial residents:", residents.value);

// スケジュール作成フォームの表示状態
const showScheduleForm = ref(false);
const formInitialDate = ref(null);
const formInitialResidentId = ref(null);

// スケジュール詳細モーダルの表示状態
const showScheduleModal = ref(false);
const selectedSchedule = ref(null);

// カレンダーの月変更時の処理
const handleMonthChange = (info) => {
    // 月が変更された場合、新しい月のデータを取得
    const newDate = dayjs(info.start).format("YYYY-MM-DD");
    const monthStart = dayjs(currentDate.value)
        .startOf("month")
        .format("YYYY-MM-DD");
    const newMonthStart = dayjs(info.start)
        .startOf("month")
        .format("YYYY-MM-DD");

    if (newMonthStart !== monthStart) {
        // Inertiaでページを再読み込み
        router.get(
            route("calendar.index"),
            { date: newDate },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }
};

// 日付クリック時の処理
const handleDateClick = (info) => {
    formInitialDate.value = dayjs(info.date).format("YYYY-MM-DD");
    formInitialResidentId.value = null;
    showScheduleForm.value = true;
};

// イベントクリック時の処理
const handleEventClick = (info) => {
    selectedSchedule.value = info.event;
    showScheduleModal.value = true;
};

// 日付ごとにスケジュールと入浴予定者をグループ化
const eventsByDate = computed(() => {
    const grouped = {};
    console.log("eventsByDate computed - events.value:", events.value);
    console.log(
        "eventsByDate computed - events.value.length:",
        events.value?.length
    );

    if (!events.value || events.value.length === 0) {
        console.warn("events.value is empty or undefined");
        return grouped;
    }

    events.value.forEach((event, index) => {
        console.log(`Processing event ${index}:`, event);
        if (!event.start) {
            console.warn(`Event ${index} has no start date:`, event);
            return;
        }
        const date = dayjs(event.start).format("YYYY-MM-DD");
        console.log(`Event ${index} date:`, date);
        if (!grouped[date]) {
            grouped[date] = {
                schedules: [],
                residents: new Set(),
            };
        }
        grouped[date].schedules.push(event);
        if (event.extendedProps?.resident_id) {
            grouped[date].residents.add(event.extendedProps.resident_id);
        }
    });
    console.log("eventsByDate computed - grouped:", grouped);
    return grouped;
});

// 日付セルのカスタムコンテンツ（日付番号のみを返す）
const dayCellContent = (info) => {
    // 日付番号のみを返し、コンテンツはdayCellDidMountで追加
    return info.dayNumberText;
};

// 日付セルがマウントされた後にコンテンツを追加
const dayCellDidMount = (info) => {
    const dateStr = dayjs(info.date).format("YYYY-MM-DD");
    const dayData = eventsByDate.value[dateStr];

    // FullCalendarのDOM構造:
    // .fc-daygrid-day
    //   .fc-daygrid-day-frame
    //     .fc-daygrid-day-number (日付番号)
    //     .fc-daygrid-day-events (デフォルトイベントコンテナ - 非表示にする)
    //     .fc-daygrid-day-bg (背景)

    // .fc-daygrid-day-frameを取得
    const dayFrame = info.el.querySelector(".fc-daygrid-day-frame");
    if (!dayFrame) return;

    // デフォルトのイベント表示を非表示にする
    const dayEvents = dayFrame.querySelector(".fc-daygrid-day-events");
    if (dayEvents) {
        dayEvents.style.display = "none";
    }

    // 既存のカスタムコンテンツを削除
    const existingContent = dayFrame.querySelector(".custom-day-content");
    if (existingContent) {
        existingContent.remove();
    }

    // カスタムコンテンツコンテナを作成
    const contentContainer = document.createElement("div");
    contentContainer.className =
        "flex-1 flex flex-col p-1 gap-1 min-h-0 overflow-hidden w-full";

    // .fc-daygrid-day-numberの後に挿入
    const dayNumberEl = dayFrame.querySelector(".fc-daygrid-day-number");
    if (dayNumberEl && dayNumberEl.nextSibling) {
        dayFrame.insertBefore(contentContainer, dayNumberEl.nextSibling);
    } else {
        dayFrame.appendChild(contentContainer);
    }

    // スケジュールセクション（上半分）- 常に作成
    const schedulesSection = document.createElement("div");
    schedulesSection.className =
        "flex flex-col gap-0.5 overflow-y-auto min-h-[60px] flex-1 border-b-4 border-gray-400 pb-2 mb-2 bg-gray-50";

    if (dayData && dayData.schedules.length > 0) {
        dayData.schedules.forEach((schedule) => {
            const scheduleItem = document.createElement("div");
            scheduleItem.className =
                "flex items-center gap-1 px-1 py-0.5 rounded text-sm text-white cursor-pointer hover:opacity-80";
            scheduleItem.style.backgroundColor =
                schedule.backgroundColor || "#3B82F6";
            scheduleItem.setAttribute("data-event-id", schedule.id);

            const timeSpan = document.createElement("span");
            timeSpan.className = "font-bold whitespace-nowrap";
            timeSpan.textContent = dayjs(schedule.start).format("HH:mm");

            const titleSpan = document.createElement("span");
            titleSpan.className =
                "flex-1 overflow-hidden text-ellipsis whitespace-nowrap";
            titleSpan.textContent =
                schedule.extendedProps?.schedule_type_name || schedule.title;

            scheduleItem.appendChild(timeSpan);
            scheduleItem.appendChild(titleSpan);

            // クリックイベントをバインド
            scheduleItem.addEventListener("click", (e) => {
                e.stopPropagation();
                const fcEvent = {
                    id: schedule.id,
                    title: schedule.title,
                    start: schedule.start,
                    end: schedule.end,
                    backgroundColor: schedule.backgroundColor,
                    borderColor: schedule.borderColor,
                    extendedProps: schedule.extendedProps,
                };
                handleEventClick({ event: fcEvent });
            });

            schedulesSection.appendChild(scheduleItem);
        });
    }

    // 入浴予定者セクション（下半分）- 常に作成
    const residentsSection = document.createElement("div");
    residentsSection.className =
        "flex flex-col min-h-[50px] flex-1 border-t-4 border-gray-400 pt-2 bg-gray-100";

    const labelDiv = document.createElement("div");
    labelDiv.className = "text-xs font-bold text-gray-600 mb-0.5";
    labelDiv.textContent = "入浴予定者";

    const listDiv = document.createElement("div");

    if (dayData && dayData.residents.size > 0) {
        listDiv.className = "text-sm text-gray-700 leading-relaxed break-words";
        const residentsList = Array.from(dayData.residents)
            .map((residentId) => {
                const resident = residents.value.find(
                    (r) => r.id === residentId
                );
                return resident ? resident.name : "";
            })
            .filter(Boolean);
        listDiv.textContent = residentsList.join(", ");
    } else {
        listDiv.className = "text-sm text-gray-400 italic";
        listDiv.textContent = "なし";
    }

    residentsSection.appendChild(labelDiv);
    residentsSection.appendChild(listDiv);

    // 両方のセクションを常に追加
    contentContainer.appendChild(schedulesSection);
    contentContainer.appendChild(residentsSection);
};

// FullCalendarの設定
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    locale: jaLocale,
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "",
    },
    buttonText: {
        today: "今日",
        month: "月",
        week: "週",
        day: "日",
    },
    events: events.value,
    eventDisplay: "none", // カスタム表示のため標準イベント表示を無効化
    height: "auto",
    editable: false,
    selectable: false,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    dayCellContent: dayCellContent, // カスタム日付セルコンテンツ
    dayCellDidMount: dayCellDidMount, // 日付セルマウント後の処理
    dayMaxEvents: false, // カスタム表示のため無効化
}));

// スケジュール作成成功時の処理
const handleScheduleCreated = () => {
    // カレンダーを再読み込み
    router.reload({ only: ["events", "monthStats"] });
};

// フォームを閉じる
const closeScheduleForm = () => {
    showScheduleForm.value = false;
    formInitialDate.value = null;
    formInitialResidentId.value = null;
};

// スケジュール更新成功時の処理
const handleScheduleUpdated = () => {
    // カレンダーを再読み込み
    router.reload({ only: ["events", "monthStats"] });
};

// スケジュール削除成功時の処理
const handleScheduleDeleted = () => {
    // カレンダーを再読み込み
    router.reload({ only: ["events", "monthStats"] });
};

// モーダルを閉じる
const closeScheduleModal = () => {
    showScheduleModal.value = false;
    selectedSchedule.value = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="カレンダー" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h1 class="text-2xl font-bold mb-6">カレンダー</h1>

                        <!-- カレンダー表示 -->
                        <div class="calendar-container">
                            <FullCalendar
                                :options="calendarOptions"
                                @datesSet="handleMonthChange"
                            />
                        </div>

                        <!-- 月間統計情報 -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg"
                            >
                                <div
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    総スケジュール数
                                </div>
                                <div class="text-2xl font-bold">
                                    {{ monthStats.total }}
                                </div>
                            </div>
                            <div
                                v-for="type in scheduleTypes"
                                :key="type.id"
                                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg"
                            >
                                <div
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    {{ type.name }}
                                </div>
                                <div class="text-2xl font-bold">
                                    {{ monthStats.by_type[type.id] || 0 }}
                                </div>
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

/* FullCalendarの日付セルを上下2分割にする */
/* .fc-daygrid-day-frameはFullCalendarが自動生成するフレーム要素 */
:deep(.fc-daygrid-day-frame) {
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
    min-height: 150px !important;
    position: relative;
}

:deep(.fc-daygrid-day) {
    height: auto;
    min-height: 150px;
}

/* 日付番号部分 - 上部に固定 */
:deep(.fc-daygrid-day-number) {
    font-weight: bold;
    font-size: 1.1rem;
    padding: 4px 8px;
    border-bottom: 1px solid #e5e7eb;
    background-color: #f9fafb;
    flex-shrink: 0 !important;
    order: 1;
}

/* デフォルトのイベント表示を非表示 */
:deep(.fc-daygrid-day-events) {
    display: none !important;
}

/* カスタムコンテンツコンテナ - 日付番号の下に配置 */
:deep(.custom-day-content) {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 4px;
    gap: 4px;
    min-height: 0;
    overflow: hidden;
    order: 2;
    width: 100%;
}

/* スケジュールセクション（上半分） */
:deep(.schedules-section) {
    flex: 1 1 50% !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 2px;
    overflow-y: auto;
    min-height: 60px !important;
    max-height: none;
    border-bottom: 4px solid #9ca3af !important;
    padding-bottom: 8px;
    margin-bottom: 8px;
    background-color: rgba(249, 250, 251, 0.5);
    position: relative;
}

/* 分割線をより明確にするための疑似要素 */
:deep(.schedules-section::after) {
    content: "";
    position: absolute;
    bottom: -4px;
    left: 0;
    right: 0;
    height: 4px;
    background-color: #6b7280;
    border-top: 1px solid #4b5563;
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

/* 入浴予定者セクション（下半分） */
:deep(.calendar-residents-section) {
    flex: 1 1 50% !important;
    display: flex !important;
    flex-direction: column !important;
    min-height: 50px !important;
    max-height: none;
    padding-top: 8px;
    border-top: 4px solid #9ca3af !important;
    margin-top: 0;
    background-color: rgba(243, 244, 246, 0.5);
    position: relative;
}

/* 分割線をより明確にするための疑似要素 */
:deep(.calendar-residents-section::before) {
    content: "";
    position: absolute;
    top: -4px;
    left: 0;
    right: 0;
    height: 4px;
    background-color: #6b7280;
    border-bottom: 1px solid #4b5563;
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
