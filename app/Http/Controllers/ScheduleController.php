<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\ScheduleStoreRequest;
use App\Services\ScheduleService;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\ScheduleConflictException;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    private ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * スケジュール一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // 権限チェック（viewAnyは実装していないため、個別のview権限でチェック）
        $this->authorize('viewAny', Schedule::class);

        try {
            $filters = [
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'resident_id' => $request->input('resident_id'),
                'schedule_type_id' => $request->input('schedule_type_id'),
            ];

            // 空の値を削除
            $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

            $schedules = $this->scheduleService->getSchedules($filters, $request->input('per_page', 20));

            return response()->json([
                'data' => $schedules->items(),
                'meta' => [
                    'current_page' => $schedules->currentPage(),
                    'per_page' => $schedules->perPage(),
                    'total' => $schedules->total(),
                    'last_page' => $schedules->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('スケジュール一覧の取得に失敗しました', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'スケジュール一覧の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * スケジュールを作成
     *
     * @param ScheduleStoreRequest $request
     * @return JsonResponse
     */
    public function store(ScheduleStoreRequest $request): JsonResponse
    {
        // 権限チェック
        $this->authorize('create', Schedule::class);

        try {
            $schedule = $this->scheduleService->createSchedule($request);

            return response()->json([
                'data' => $schedule->load(['calendarDate', 'resident', 'scheduleType', 'creator']),
                'message' => 'スケジュールを作成しました。',
            ], 201);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるスケジュール作成試行', $e->getLogContext());

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        } catch (ScheduleConflictException $e) {
            Log::warning('スケジュール重複による作成試行', $e->getLogContext());

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'SCHEDULE_CONFLICT',
            ], 409);
        } catch (\Exception $e) {
            Log::error('スケジュールの作成に失敗しました', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'スケジュールの作成に失敗しました。',
            ], 500);
        }
    }
}
