<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
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

// FullCalendarのref
const calendarRef = ref(null);
const calendarKey = ref(0); // コンポーネント再生成用のキー

// スケジュール作成フォームの表示状態
const showScheduleForm = ref(false);
const formInitialDate = ref(null);
const formInitialResidentId = ref(null);

// スケジュール詳細モーダルの表示状態
const showScheduleModal = ref(false);
const selectedSchedule = ref(null);

// props.eventsの変更を監視してeventsを更新
// props.eventsの変更を監視してeventsを更新
watch(
    () => props.events,
    (newEvents) => {
        if (newEvents) {
            events.value = newEvents;
            // コンポーネントを再生成して完全にリセット
            calendarKey.value++;
        }
    },
    { deep: true }
);

// マウント時の処理
onMounted(() => {
    // 初期表示時も念のためキーを更新
    // calendarKey.value++; 
    // ↑ onMountedでの更新は不要かもしれないが、念のため
});

// カレンダーの月変更時の処理
const handleMonthChange = (info) => {
    // 月が変更された場合、新しい月のデータを取得
    // info.startはグリッドの開始日（前月の日付が含まれる場合がある）なので、
    // info.view.currentStart（表示中の月の1日）を使用する
    const newDate = dayjs(info.view.currentStart).format("YYYY-MM-DD");
    const monthStart = dayjs(currentDate.value)
        .startOf("month")
        .format("YYYY-MM-DD");
    const newMonthStart = dayjs(info.view.currentStart)
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
                only: ["events", "residents", "monthStats", "currentDate"], // 必要なデータのみ取得
            }
        );
    }
};

// 日付クリック時の処理
const handleDateClick = (info) => {
    // イベントがクリックされた場合は何もしない（eventClickで処理される）
    // カスタムスケジュールアイテムがクリックされた場合もdateClickが発火する可能性があるため
    // クリックされた要素がカスタムスケジュールアイテムかどうかを確認
    const clickedElement = info.jsEvent?.target;
    if (clickedElement) {
        // カスタムスケジュールアイテムまたはその子要素がクリックされた場合は無視
        const scheduleItem = clickedElement.closest("[data-event-id]");
        if (scheduleItem) {
            return; // イベントクリックで処理されるため、dateClickは無視
        }
    }

    formInitialDate.value = dayjs(info.date).format("YYYY-MM-DD");
    formInitialResidentId.value = null;
    showScheduleForm.value = true;
};

// イベントクリック時の処理
const handleEventClick = (info) => {
    // スケジュール作成フォームを閉じる（誤って開かないように）
    showScheduleForm.value = false;

    selectedSchedule.value = info.event;
    showScheduleModal.value = true;
};

// 日付ごとにスケジュールと入浴予定者をグループ化
const eventsByDate = computed(() => {
    const grouped = {};
    
    if (!events.value || events.value.length === 0) {
        return grouped;
    }

    events.value.forEach((event, index) => {
        if (!event.start) return;
        
        const date = dayjs(event.start).format("YYYY-MM-DD");
        
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
    return grouped;
});

// カレンダーのセルコンテンツを生成する関数
const renderDayCellContent = (info) => {
    const dateStr = dayjs(info.date).format("YYYY-MM-DD");
    const dayData = eventsByDate.value[dateStr];
    
    const isToday = dateStr === dayjs().format("YYYY-MM-DD");
    const isDarkMode = document.documentElement.classList.contains("dark");

    // コンテナ
    const container = document.createElement("div");
    container.className = "flex flex-col h-full w-full overflow-hidden relative group transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-800/50";
    
    // 1. 日付番号エリア
    const headerDiv = document.createElement("div");
    headerDiv.className = "flex justify-between items-start p-1.5";
    
    // 日付番号
    const dayNumberSpan = document.createElement("span");
    dayNumberSpan.className = "text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full transition-colors";
    dayNumberSpan.innerText = info.dayNumberText.replace('日', ''); // "日"を削除して数字のみに
    
    // 当日のスタイル適用
    if (isToday) {
        dayNumberSpan.classList.add(
            "bg-blue-600",
            "text-white",
            "shadow-md",
            "dark:bg-blue-500"
        );
    } else {
        dayNumberSpan.classList.add("text-gray-700", "dark:text-gray-300");
    }
    
    headerDiv.appendChild(dayNumberSpan);
    container.appendChild(headerDiv);

    // 2. カスタムコンテンツエリア
    const contentContainer = document.createElement("div");
    contentContainer.className = "flex-1 flex flex-col px-1 pb-1 gap-1 min-h-0 overflow-hidden w-full";

    // スケジュールリスト
    const schedulesSection = document.createElement("div");
    schedulesSection.className = "flex flex-col gap-1 overflow-y-auto flex-1 min-h-0";

    if (dayData && dayData.schedules.length > 0) {
        dayData.schedules.forEach((schedule) => {
            const scheduleItem = document.createElement("div");
            // モダンなピル型デザイン
            scheduleItem.className = "flex items-center gap-1.5 px-2 py-1 rounded-md text-xs text-white cursor-pointer hover:opacity-90 transition-all shadow-sm transform hover:scale-[1.02]";
            scheduleItem.style.backgroundColor = schedule.backgroundColor || "#3B82F6";
            scheduleItem.style.borderLeft = `3px solid ${schedule.borderColor || 'rgba(0,0,0,0.1)'}`;
            scheduleItem.setAttribute("data-event-id", schedule.id);

            const timeSpan = document.createElement("span");
            timeSpan.className = "font-bold whitespace-nowrap opacity-90 text-[10px]";
            timeSpan.textContent = dayjs(schedule.start).format("HH:mm");

            const titleSpan = document.createElement("span");
            titleSpan.className = "flex-1 overflow-hidden text-ellipsis whitespace-nowrap font-medium";
            titleSpan.textContent = schedule.extendedProps?.schedule_type_name || schedule.title;

            scheduleItem.appendChild(timeSpan);
            scheduleItem.appendChild(titleSpan);

            // クリックイベント
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
    contentContainer.appendChild(schedulesSection);

    // 入浴予定者セクション（下部に控えめに表示）
    if (dayData && dayData.residents.size > 0) {
        const residentsSection = document.createElement("div");
        residentsSection.className = "mt-auto pt-1 border-t border-gray-100 dark:border-gray-700";

        const residentsContainer = document.createElement("div");
        residentsContainer.className = "flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400";
        
        // アイコン
        const icon = document.createElement("i");
        icon.className = "bi bi-droplet-fill text-blue-400 text-[10px]";
        residentsContainer.appendChild(icon);

        const listSpan = document.createElement("span");
        listSpan.className = "truncate";
        const residentsList = Array.from(dayData.residents)
            .map((residentId) => {
                const resident = residents.value.find((r) => r.id === residentId);
                return resident ? resident.name : "";
            })
            .filter(Boolean);
        listSpan.textContent = residentsList.join(", ");
        
        residentsContainer.appendChild(listSpan);
        residentsSection.appendChild(residentsContainer);
        contentContainer.appendChild(residentsSection);
    }

    container.appendChild(contentContainer);

    return { domNodes: [container] };
};

// 日付セルがマウントされた後の処理（背景のクリアなど）
const dayCellDidMount = (info) => {
    // デフォルトのイベントコンテナなどを非表示にする必要があればここで
    const dayFrame = info.el.querySelector(".fc-daygrid-day-frame");
    if (dayFrame) {
        // デフォルトのイベント表示を非表示
        const dayEvents = dayFrame.querySelector(".fc-daygrid-day-events");
        if (dayEvents) {
            dayEvents.style.display = "none";
        }
        
        // 背景を透明に（必要な場合）
        const dayBg = dayFrame.querySelector(".fc-daygrid-day-bg");
        if (dayBg) {
            dayBg.style.display = "none";
        }
    }
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
    // events: events.value, // ここでの指定を削除し、プロップとして渡す
    eventDisplay: "none", // カスタム表示のため標準イベント表示を無効化
    height: "auto",
    editable: false,
    selectable: false,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    dayCellContent: renderDayCellContent, // カスタム日付セルコンテンツ（全置換）
    dayCellDidMount: dayCellDidMount, // 日付セルマウント後の処理（スタイル調整のみ）
    dayMaxEvents: false, // カスタム表示のため無効化
    dayHeaderFormat: { weekday: 'short' }, // 曜日を短縮形に（月、火...）
}));

// スケジュール作成成功時の処理
const handleScheduleCreated = (newEvent) => {

    if (newEvent) {
        // 作成されたスケジュールの日付を取得
        const createdDate = newEvent.start
            ? dayjs(newEvent.start).format("YYYY-MM-DD")
            : null;

        // 作成されたイベントを直接eventsに追加
        events.value = [...events.value, newEvent];

        // FullCalendarのイベントを更新
        if (calendarRef.value) {
            const calendarApi = calendarRef.value.getApi();

            // 新しいイベントをFullCalendarに追加
            calendarApi.addEvent(newEvent);
        }

        // eventsByDateが更新されるのを待つ
        nextTick(() => {
            // FullCalendarのイベントを再読み込み
            if (calendarRef.value) {
                const calendarApi = calendarRef.value.getApi();
                calendarApi.render();
            }
        });

        // 月間統計情報も更新
        if (monthStats.value) {
            monthStats.value = {
                ...monthStats.value,
                total: monthStats.value.total + 1,
            };
        }
    }
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
const handleScheduleDeleted = (deletedScheduleId) => {

    // まずモーダルを閉じる（確実に閉じるため）
    closeScheduleModal();

    // スケジュール作成フォームも閉じる（誤って開かないように）
    showScheduleForm.value = false;

    if (deletedScheduleId) {
        // 削除される前のスケジュールの日付を取得
        const deletedEvent = events.value.find(
            (event) => event.id === deletedScheduleId
        );
        const deletedDate = deletedEvent
            ? dayjs(deletedEvent.start).format("YYYY-MM-DD")
            : null;

        // 削除されたスケジュールをeventsから直接削除
        events.value = events.value.filter(
            (event) => event.id !== deletedScheduleId
        );

        // FullCalendarのイベントを更新
        if (calendarRef.value) {
            const calendarApi = calendarRef.value.getApi();

            // 削除されたイベントをFullCalendarからも削除
            const eventToRemove = calendarApi.getEventById(deletedScheduleId);
            if (eventToRemove) {
                eventToRemove.remove();
            }
        }

        // eventsByDateが更新されるのを待つ
        nextTick(() => {
            // FullCalendarのイベントを再読み込み
            if (calendarRef.value) {
                const calendarApi = calendarRef.value.getApi();
                calendarApi.render();
            }
        });

        // 月間統計情報も更新
        if (monthStats.value) {
            monthStats.value = {
                ...monthStats.value,
                total: Math.max(0, monthStats.value.total - 1),
            };
        }
    }
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

        <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- ヘッダーエリア -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">カレンダー</h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">スケジュールの確認と管理ができます</p>
                    </div>
                </div>

                <!-- カレンダーメインエリア -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <!-- カレンダー表示 -->
                        <div class="calendar-container">
                            <FullCalendar
                                :key="calendarKey"
                                ref="calendarRef"
                                :options="calendarOptions"
                                @datesSet="handleMonthChange"
                            />
                        </div>
                    </div>
                </div>

                <!-- 月間統計情報（カードデザイン） -->
                <div class="mt-8">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 px-1">今月の統計</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 総スケジュール数 -->
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-transform hover:-translate-y-1 duration-300">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">総スケジュール</div>
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                                    <i class="bi bi-calendar-check text-lg"></i>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                                {{ monthStats.total }}
                            </div>
                        </div>

                        <!-- スケジュールタイプ別 -->
                        <div
                            v-for="type in scheduleTypes"
                            :key="type.id"
                            class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition-transform hover:-translate-y-1 duration-300"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ type.name }}</div>
                                <div 
                                    class="p-2 rounded-lg"
                                    :style="{ backgroundColor: type.color ? `${type.color}20` : '#e5e7eb', color: type.color || '#6b7280' }"
                                >
                                    <i class="bi bi-circle-fill text-xs"></i>
                                </div>
                            </div>
                            <div class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                                {{ monthStats.by_type[type.id] || 0 }}
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

<style scoped>
/* カレンダーコンテナ */
.calendar-container {
    font-family: 'Inter', sans-serif;
}

/* FullCalendarの全体スタイル調整 */
:deep(.fc) {
    --fc-border-color: #f3f4f6; /* 枠線を薄く */
    --fc-today-bg-color: transparent; /* 今日の背景色をクリア */
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

/* 日付セルフレーム */
:deep(.fc-daygrid-day-frame) {
    min-height: 120px !important;
    transition: background-color 0.2s;
}

/* 今日のセル */
:deep(.fc-day-today) {
    background-color: transparent !important;
}

/* スクロールバーのカスタマイズ */
:deep(::-webkit-scrollbar) {
    width: 4px;
    height: 4px;
}

:deep(::-webkit-scrollbar-track) {
    background: transparent;
}

:deep(::-webkit-scrollbar-thumb) {
    background: #d1d5db;
    border-radius: 2px;
}

:deep(::-webkit-scrollbar-thumb:hover) {
    background: #9ca3af;
}

.dark :deep(::-webkit-scrollbar-thumb) {
    background: #4b5563;
}
</style>
