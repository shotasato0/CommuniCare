<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\CalendarDate;
use App\Models\Resident;
use App\Models\ScheduleType;
use App\Http\Requests\Schedule\ScheduleStoreRequest;
use App\Http\Requests\Schedule\ScheduleUpdateRequest;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\ScheduleConflictException;
use App\Traits\TenantBoundaryCheckTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleService
{
    use TenantBoundaryCheckTrait;

    /**
     * スケジュール一覧を取得（ページネーション対応）
     *
     * @param array $filters フィルタ条件
     * @param int $perPage 1ページあたりの件数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getSchedules(array $filters = [], int $perPage = 20)
    {
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        $query = Schedule::where('tenant_id', $currentTenantId)
            ->with([
                'calendarDate',
                'resident',
                'creator' => function ($q) use ($currentTenantId) {
                    $q->select('id', 'name', 'tenant_id')
                      ->where('tenant_id', $currentTenantId);
                },
            ]);

        // 日付範囲でフィルタリング
        if (isset($filters['date_from'])) {
            $query->whereHas('calendarDate', function ($q) use ($filters) {
                $q->where('date', '>=', $filters['date_from']);
            });
        }

        if (isset($filters['date_to'])) {
            $query->whereHas('calendarDate', function ($q) use ($filters) {
                $q->where('date', '<=', $filters['date_to']);
            });
        }

        // 利用者IDでフィルタリング
        if (isset($filters['resident_id'])) {
            $query->where('resident_id', $filters['resident_id']);
        }


        return $query->orderBy('start_time')->paginate($perPage);
    }

    /**
     * スケジュールを作成
     *
     * @param ScheduleStoreRequest $request
     * @return Schedule
     * @throws TenantViolationException
     * @throws ScheduleConflictException
     */
    public function createSchedule(ScheduleStoreRequest $request): Schedule
    {
        $validated = $request->validated();
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // 日付マスタの取得または作成（トランザクション外で実行）
        $calendarDate = $this->ensureCalendarDate($validated['date'], $currentTenantId);

        return DB::transaction(function () use ($validated, $currentTenantId, $currentUser, $calendarDate) {
            // スケジュール作成
            $schedule = Schedule::create([
                'tenant_id' => $currentTenantId,
                'calendar_date_id' => $calendarDate->id,
                'resident_id' => $validated['resident_id'] ?? null,
                'schedule_name' => $validated['schedule_name'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'memo' => $validated['memo'] ?? null,
                'created_by' => $currentUser->id,
            ]);

            Log::info('スケジュールを作成しました', [
                'schedule_id' => $schedule->id,
                'tenant_id' => $currentTenantId,
                'schedule_name' => $validated['schedule_name'],
                'date' => $validated['date'],
            ]);

            return $schedule;
        });
    }

    /**
     * 日付マスタが存在しない場合は作成して返す
     *
     * @param string $date YYYY-MM-DD形式
     * @param string $tenantId
     * @return CalendarDate
     */
    private function ensureCalendarDate(string $date, string $tenantId): CalendarDate
    {
        $carbonDate = Carbon::parse($date);
        $dateString = $carbonDate->format('Y-m-d');

        // firstOrCreateで原子的に取得または作成（グローバルスコープを無視）
        // 並行処理での競合も安全に処理される
        try {
            $calendarDate = CalendarDate::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'date' => $dateString,
                ],
                [
                    'day_of_week' => $carbonDate->dayOfWeek,
                    'is_holiday' => false,
                    'holiday_name' => null,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // UNIQUE制約違反の場合、再度検索
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                $calendarDate = CalendarDate::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('date', $dateString)
                    ->first();
                
                if (!$calendarDate) {
                    throw new \RuntimeException("CalendarDateが見つかりません: tenant_id={$tenantId}, date={$dateString}");
                }
            } else {
                throw $e;
            }
        }

        return $calendarDate;
    }

    /**
     * スケジュールを更新
     *
     * @param Schedule $schedule
     * @param ScheduleUpdateRequest $request
     * @return Schedule
     * @throws TenantViolationException
     * @throws ScheduleConflictException
     */
    public function updateSchedule(Schedule $schedule, ScheduleUpdateRequest $request): Schedule
    {
        $validated = $request->validated();
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // テナント境界チェック
        $this->validateTenantBoundary($schedule);

        // 日付マスタの取得または作成（トランザクション外で実行）
        $calendarDate = $this->ensureCalendarDate($validated['date'], $currentTenantId);

        return DB::transaction(function () use ($schedule, $validated, $currentTenantId, $currentUser, $calendarDate) {
            // 利用者のテナント境界チェック
            $resident = Resident::findOrFail($validated['resident_id']);
            $this->validateTenantBoundary($resident);

            // 詳細な重複チェック（M2：時間帯の重複検証）
            $this->validateNoConflict(
                $validated['resident_id'],
                $calendarDate->id,
                $validated['start_time'],
                $validated['end_time'],
                $schedule->id // 自分自身を除外
            );

            // スケジュール更新
            $schedule->update([
                'calendar_date_id' => $calendarDate->id,
                'resident_id' => $validated['resident_id'],
                'schedule_name' => $validated['schedule_name'] ?? $schedule->schedule_name,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'memo' => $validated['memo'] ?? null,
            ]);

            Log::info('スケジュールを更新しました', [
                'schedule_id' => $schedule->id,
                'tenant_id' => $currentTenantId,
                'resident_id' => $validated['resident_id'],
                'date' => $validated['date'],
            ]);

            return $schedule->fresh();
        });
    }

    /**
     * スケジュールを削除
     *
     * @param Schedule $schedule
     * @return void
     * @throws TenantViolationException
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        $currentUser = Auth::user();
        $currentTenantId = $currentUser->tenant_id;

        // テナント境界チェック
        $this->validateTenantBoundary($schedule);

        DB::transaction(function () use ($schedule, $currentTenantId) {
            $scheduleId = $schedule->id;
            $residentId = $schedule->resident_id;
            $calendarDate = $schedule->calendarDate;

            $schedule->delete();

            Log::info('スケジュールを削除しました', [
                'schedule_id' => $scheduleId,
                'tenant_id' => $currentTenantId,
                'resident_id' => $residentId,
                'date' => $calendarDate ? $calendarDate->date->toDateString() : null,
            ]);
        });
    }

    /**
     * 重複スケジュールをチェック（M2：時間帯の重複検証）
     *
     * @param int $residentId
     * @param int $calendarDateId
     * @param string $startTime HH:MM形式
     * @param string $endTime HH:MM形式
     * @param int|null $excludeScheduleId 更新時は自分自身を除外
     * @return void
     * @throws ScheduleConflictException
     */
    private function validateNoConflict(
        int $residentId,
        int $calendarDateId,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): void {
        $currentTenantId = Auth::user()->tenant_id;

        // 時間帯の重複をチェック
        // 重複条件: 開始時刻 < 既存の終了時刻 かつ 終了時刻 > 既存の開始時刻
        $query = Schedule::where('tenant_id', $currentTenantId)
            ->where('resident_id', $residentId)
            ->where('calendar_date_id', $calendarDateId)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    // 新しいスケジュールの開始時刻が既存のスケジュールの終了時刻より前
                    // かつ新しいスケジュールの終了時刻が既存のスケジュールの開始時刻より後
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $conflictingSchedule = $query->first();

        if ($conflictingSchedule) {
            $calendarDate = CalendarDate::findOrFail($calendarDateId);
            throw new ScheduleConflictException(
                residentId: $residentId,
                date: $calendarDate->date->toDateString(),
                startTime: $startTime,
                endTime: $endTime,
                conflictingScheduleId: $conflictingSchedule->id
            );
        }
    }
}

