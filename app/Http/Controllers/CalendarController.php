<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ScheduleService;
use App\Models\Resident;
use App\Models\ScheduleType;
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
        $schedules = $this->scheduleService->getSchedules($filters, PHP_INT_MAX);

        // FullCalendar用のイベント形式に変換
        $events = $schedules->map(function ($schedule) {
            // Carbonを使用してISO 8601形式の日時文字列を生成
            $startDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->end_time);
            
            return [
                'id' => $schedule->id,
                'title' => $schedule->resident->name . ' - ' . $schedule->scheduleType->name,
                'start' => $startDateTime->toIso8601String(),
                'end' => $endDateTime->toIso8601String(),
                'backgroundColor' => $schedule->scheduleType->color ?? '#3B82F6',
                'borderColor' => $schedule->scheduleType->color ?? '#3B82F6',
                'extendedProps' => [
                    'resident_id' => $schedule->resident_id,
                    'resident_name' => $schedule->resident->name,
                    'schedule_type_id' => $schedule->schedule_type_id,
                    'schedule_type_name' => $schedule->scheduleType->name,
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

        // スケジュール種別一覧を取得
        $scheduleTypes = ScheduleType::where('tenant_id', $currentTenantId)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'color' => $type->color ?? '#3B82F6',
                    'description' => $type->description,
                ];
            });

        // 月間統計情報を計算
        $monthStats = [
            'total' => $schedules->count(),
            'by_type' => $scheduleTypes->mapWithKeys(function ($type) use ($schedules) {
                return [$type['id'] => $schedules->where('schedule_type_id', $type['id'])->count()];
            }),
        ];

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'residents' => $residents,
            'scheduleTypes' => $scheduleTypes,
            'monthStats' => $monthStats,
            'currentDate' => $date,
        ]);
    }
}
