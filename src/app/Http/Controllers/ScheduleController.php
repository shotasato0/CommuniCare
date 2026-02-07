<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\ScheduleStoreRequest;
use App\Http\Requests\Schedule\ScheduleUpdateRequest;
use App\Services\ScheduleService;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\ScheduleConflictException;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

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
        Gate::authorize('viewAny', Schedule::class);

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
     * @return JsonResponse|RedirectResponse
     */
    public function store(ScheduleStoreRequest $request)
    {
        $user = Auth::user();
        
        // ゲストユーザーでロールが割り当てられていない場合は割り当てる
        if ($user->guest_session_id !== null) {
            $user->load('roles');
            if ($user->roles->isEmpty()) {
                $userRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
                $user->assignRole($userRole);
                $user->refresh();
                Log::info('ゲストユーザーにロールを割り当てました（スケジュール作成時）', [
                    'user_id' => $user->id,
                    'role' => 'user',
                ]);
            }
        }
        
        // デバッグ: 現在のユーザーの権限状態をログに記録
        Log::info('スケジュール作成試行', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_tenant_id' => $user->tenant_id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'has_schedules_create_permission' => $user->hasPermissionTo('schedules.create'),
        ]);

        // 権限チェック
        Gate::authorize('create', Schedule::class);

        try {
            $schedule = $this->scheduleService->createSchedule($request);
            $schedule->load(['calendarDate', 'resident', 'scheduleType', 'creator']);

            // FullCalendar用のイベント形式に変換
            $startDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->calendarDate->date->format('Y-m-d') . ' ' . $schedule->end_time);
            
            $eventData = [
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

            // Inertia.jsのリクエストかどうかを判定
            if ($request->header('X-Inertia')) {
                // Inertia.jsリクエストの場合: 作成されたイベントデータを返す
                // redirect()->back()を使わず、JSONレスポンスでイベントデータを返す
                return response()->json([
                    'success' => true,
                    'message' => 'スケジュールを作成しました。',
                    'event' => $eventData,
                ], 201);
            }

            // APIリクエストの場合
            return response()->json([
                'data' => $schedule,
                'event' => $eventData,
                'message' => 'スケジュールを作成しました。',
            ], 201);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるスケジュール作成試行', $e->getLogContext());

            // axiosリクエストの場合もJSONレスポンスを返す
            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        } catch (ScheduleConflictException $e) {
            Log::warning('スケジュール重複による作成試行', $e->getLogContext());

            // axiosリクエストの場合もJSONレスポンスを返す
            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'SCHEDULE_CONFLICT',
            ], 409);
        } catch (\Exception $e) {
            Log::error('スケジュールの作成に失敗しました', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // axiosリクエストの場合もJSONレスポンスを返す
            return response()->json([
                'message' => 'スケジュールの作成に失敗しました。',
            ], 500);
        }
    }

    /**
     * スケジュールを更新
     *
     * @param ScheduleUpdateRequest $request
     * @param int $schedule
     * @return JsonResponse
     */
    public function update(ScheduleUpdateRequest $request, int $schedule): JsonResponse
    {
        // ルートモデルバインディング時のテナントスコープ影響を避け、明示的に取得
        $schedule = Schedule::withoutGlobalScopes()->findOrFail($schedule);
        
        // テナント境界チェック
        if ($schedule->tenant_id !== Auth::user()->tenant_id) {
            Log::critical('テナント境界違反によるスケジュール更新試行', [
                'current_tenant_id' => Auth::user()->tenant_id,
                'schedule_tenant_id' => $schedule->tenant_id,
                'schedule_id' => $schedule->id,
            ]);
            
            return response()->json([
                'message' => '他のテナントのスケジュールにアクセスすることはできません。',
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        }
        
        // 権限チェック
        Gate::authorize('update', $schedule);

        try {
            $schedule = $this->scheduleService->updateSchedule($schedule, $request);

            return response()->json([
                'data' => $schedule->load(['calendarDate', 'resident', 'scheduleType', 'creator']),
                'message' => 'スケジュールを更新しました。',
            ]);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるスケジュール更新試行', $e->getLogContext());

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        } catch (ScheduleConflictException $e) {
            Log::warning('スケジュール重複による更新試行', $e->getLogContext());

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'SCHEDULE_CONFLICT',
            ], 409);
        } catch (\Exception $e) {
            Log::error('スケジュールの更新に失敗しました', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'スケジュールの更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * スケジュールを削除
     *
     * @param int $schedule
     * @return JsonResponse
     */
    public function destroy(int $schedule): JsonResponse
    {
        // ルートモデルバインディング時のテナントスコープ影響を避け、明示的に取得
        $schedule = Schedule::withoutGlobalScopes()->findOrFail($schedule);
        
        // テナント境界チェック
        if ($schedule->tenant_id !== Auth::user()->tenant_id) {
            Log::critical('テナント境界違反によるスケジュール削除試行', [
                'current_tenant_id' => Auth::user()->tenant_id,
                'schedule_tenant_id' => $schedule->tenant_id,
                'schedule_id' => $schedule->id,
            ]);
            
            return response()->json([
                'message' => '他のテナントのスケジュールにアクセスすることはできません。',
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        }
        
        try {
            $currentUser = Auth::user();
            
            // 権限チェック（schedules.delete権限があるか）
            if (!$currentUser->hasPermissionTo('schedules.delete')) {
                Log::warning('権限不足によるスケジュール削除試行', [
                    'user_id' => $currentUser->id,
                    'schedule_id' => $schedule->id,
                    'reason' => 'missing_permission',
                ]);

                return response()->json([
                    'message' => 'スケジュールを削除する権限がありません。',
                    'error_code' => 'AUTHORIZATION_FAILED',
                ], 403);
            }

            // 所有権チェック（一般ユーザーは自分が作成したスケジュールのみ削除可能）
            if (!$currentUser->hasRole('admin') && $schedule->created_by !== $currentUser->id) {
                Log::warning('所有権違反によるスケジュール削除試行', [
                    'user_id' => $currentUser->id,
                    'schedule_id' => $schedule->id,
                    'schedule_created_by' => $schedule->created_by,
                    'reason' => 'ownership_violation',
                ]);

                return response()->json([
                    'message' => '自分が作成したスケジュールのみ削除できます。',
                    'error_code' => 'OWNERSHIP_VIOLATION',
                ], 403);
            }

            // 権限チェック（Policyを使用）
            Gate::authorize('delete', $schedule);

            $this->scheduleService->deleteSchedule($schedule);

            return response()->json([
                'message' => 'スケジュールを削除しました。',
            ]);
        } catch (AuthorizationException $e) {
            Log::warning('権限不足によるスケジュール削除試行', [
                'user_id' => Auth::user()->id,
                'schedule_id' => $schedule->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'このスケジュールを削除する権限がありません。',
                'error_code' => 'AUTHORIZATION_FAILED',
            ], 403);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるスケジュール削除試行', $e->getLogContext());

            return response()->json([
                'message' => $e->getUserMessage(),
                'error_code' => 'TENANT_VIOLATION',
            ], 403);
        } catch (\Exception $e) {
            Log::error('スケジュールの削除に失敗しました', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'スケジュールの削除に失敗しました。',
            ], 500);
        }
    }
}
