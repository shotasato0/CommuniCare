<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ScheduleService;
use App\Models\Resident;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller
{
    private ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * カレンダーページを表示
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // リクエストから日付を取得（デフォルトは今月）
        $date = $request->input('date', now()->format('Y-m-d'));
        $carbonDate = Carbon::parse($date);

        // 現在の月の開始日と終了日を取得
        $startOfMonth = $carbonDate->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $carbonDate->copy()->endOfMonth()->format('Y-m-d');

        // スケジュールを取得
        $filters = [
            'date_from' => $startOfMonth,
            'date_to' => $endOfMonth,
        ];
        // 月間データなので全件取得（perPageをnullにすることで全件取得）
        $schedulesPaginator = $this->scheduleService->getSchedules($filters, PHP_INT_MAX);
        // Paginatorからコレクションを取得
        $schedules = $schedulesPaginator->getCollection();

        // FullCalendar用のイベント形式に変換
        $events = $schedules->map(function ($schedule) {
            // Carbonを使用してISO 8601形式の日時文字列を生成
            $startDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->end_time);
            
            return [
                'id' => $schedule->id,
                'title' => ($schedule->resident ? $schedule->resident->name . ' - ' : '') . $schedule->schedule_name,
                'start' => $startDateTime->toIso8601String(),
                'end' => $endDateTime->toIso8601String(),
                'backgroundColor' => '#3B82F6',
                'borderColor' => '#3B82F6',
                'extendedProps' => [
                    'resident_id' => $schedule->resident_id,
                    'resident_name' => $schedule->resident ? $schedule->resident->name : null,
                    'schedule_name' => $schedule->schedule_name,
                    'memo' => $schedule->memo,
                ],
            ];
        });

        // 利用者一覧を取得
        $residents = Resident::where('tenant_id', $currentTenantId)
            ->orderBy('name')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->name,
                    'unit_id' => $resident->unit_id,
                ];
            });

        // 月間統計情報を計算
        $monthStats = [
            'total' => $schedules->count(),
        ];

        \Illuminate\Support\Facades\Log::info('CalendarController::index', [
            'date' => $date,
            'events_count' => $events->count(),
            'residents_count' => $residents->count(),
            'first_event' => $events->first(),
        ]);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'residents' => $residents,
            'monthStats' => $monthStats,
            'currentDate' => $date,
        ]);
    }

    /**
     * 週間カレンダーページを表示
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function week(Request $request)
    {
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // リクエストから日付を取得（デフォルトは今週）
        $date = $request->input('date', now()->format('Y-m-d'));
        $carbonDate = Carbon::parse($date);

        // 現在の週の開始日と終了日を取得（月曜日始まり）
        $startOfWeek = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = $carbonDate->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        // スケジュールを取得
        $filters = [
            'date_from' => $startOfWeek,
            'date_to' => $endOfWeek,
        ];
        $schedulesPaginator = $this->scheduleService->getSchedules($filters, PHP_INT_MAX);
        // Paginatorからコレクションを取得
        $schedules = $schedulesPaginator->getCollection();

        // FullCalendar用のイベント形式に変換
        $events = $schedules->map(function ($schedule) {
            $startDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->end_time);
            
            return [
                'id' => $schedule->id,
                'title' => ($schedule->resident ? $schedule->resident->name . ' - ' : '') . $schedule->schedule_name,
                'start' => $startDateTime->toIso8601String(),
                'end' => $endDateTime->toIso8601String(),
                'backgroundColor' => '#3B82F6',
                'borderColor' => '#3B82F6',
                'extendedProps' => [
                    'resident_id' => $schedule->resident_id,
                    'resident_name' => $schedule->resident ? $schedule->resident->name : null,
                    'schedule_name' => $schedule->schedule_name,
                    'memo' => $schedule->memo,
                ],
            ];
        });

        // 利用者一覧を取得
        $residents = Resident::where('tenant_id', $currentTenantId)
            ->orderBy('name')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->name,
                    'unit_id' => $resident->unit_id,
                ];
            });

        return Inertia::render('Calendar/Week', [
            'events' => $events,
            'residents' => $residents,
            'currentDate' => $date,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ]);
    }

    /**
     * 日間カレンダーページを表示
     *
     * @param Request $request
     * @param string|null $date
     * @return \Inertia\Response
     */
    public function day(Request $request, ?string $date = null)
    {
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // リクエストから日付を取得（デフォルトは今日）
        $date = $date ?? $request->input('date', now()->format('Y-m-d'));
        $carbonDate = Carbon::parse($date);

        // 指定日のスケジュールを取得
        $filters = [
            'date_from' => $carbonDate->format('Y-m-d'),
            'date_to' => $carbonDate->format('Y-m-d'),
        ];
        $schedulesPaginator = $this->scheduleService->getSchedules($filters, PHP_INT_MAX);
        // Paginatorからコレクションを取得
        $schedules = $schedulesPaginator->getCollection();

        // FullCalendar用のイベント形式に変換
        $events = $schedules->map(function ($schedule) {
            $startDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->end_time);
            
            return [
                'id' => $schedule->id,
                'title' => ($schedule->resident ? $schedule->resident->name . ' - ' : '') . $schedule->schedule_name,
                'start' => $startDateTime->toIso8601String(),
                'end' => $endDateTime->toIso8601String(),
                'backgroundColor' => '#3B82F6',
                'borderColor' => '#3B82F6',
                'extendedProps' => [
                    'resident_id' => $schedule->resident_id,
                    'resident_name' => $schedule->resident ? $schedule->resident->name : null,
                    'schedule_name' => $schedule->schedule_name,
                    'memo' => $schedule->memo,
                ],
            ];
        });

        // 利用者一覧を取得
        $residents = Resident::where('tenant_id', $currentTenantId)
            ->orderBy('name')
            ->get()
            ->map(function ($resident) {
                return [
                    'id' => $resident->id,
                    'name' => $resident->name,
                    'unit_id' => $resident->unit_id,
                ];
            });

        return Inertia::render('Calendar/Day', [
            'events' => $events,
            'residents' => $residents,
            'currentDate' => $date,
        ]);
    }
}
