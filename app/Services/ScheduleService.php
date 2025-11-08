<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\CalendarDate;
use App\Models\Resident;
use App\Models\ScheduleType;
use App\Http\Requests\Schedule\ScheduleStoreRequest;
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
                'scheduleType',
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

        // 種別IDでフィルタリング
        if (isset($filters['schedule_type_id'])) {
            $query->where('schedule_type_id', $filters['schedule_type_id']);
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
            // 利用者のテナント境界チェック
            $resident = Resident::findOrFail($validated['resident_id']);
            $this->validateTenantBoundary($resident);

            // 種別のテナント境界チェック
            $scheduleType = ScheduleType::findOrFail($validated['schedule_type_id']);
            $this->validateTenantBoundary($scheduleType);

            // 簡易重複チェック（M1では同時刻一致のみ）
            $this->validateNoConflict(
                $validated['resident_id'],
                $calendarDate->id,
                $validated['start_time'],
                $validated['end_time']
            );

            // スケジュール作成
            $schedule = Schedule::create([
                'tenant_id' => $currentTenantId,
                'calendar_date_id' => $calendarDate->id,
                'resident_id' => $validated['resident_id'],
                'schedule_type_id' => $validated['schedule_type_id'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'memo' => $validated['memo'] ?? null,
                'created_by' => $currentUser->id,
            ]);

            Log::info('スケジュールを作成しました', [
                'schedule_id' => $schedule->id,
                'tenant_id' => $currentTenantId,
                'resident_id' => $validated['resident_id'],
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

        // 既存のレコードを検索（グローバルスコープを無視）
        // tenancyが初期化されている場合でも、withoutGlobalScopesで検索可能
        $calendarDate = CalendarDate::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('date', $dateString)
            ->first();

        // 存在しない場合は作成（重複エラーをキャッチ）
        if (!$calendarDate) {
            try {
                $calendarDate = CalendarDate::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId,
                    'date' => $dateString,
                    'day_of_week' => $carbonDate->dayOfWeek,
                    'is_holiday' => false,
                    'holiday_name' => null,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 重複エラーの場合、再度検索
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    $calendarDate = CalendarDate::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('date', $dateString)
                        ->first();
                    
                    if (!$calendarDate) {
                        // それでも見つからない場合は例外をスロー
                        throw new \RuntimeException("CalendarDateが見つかりません: tenant_id={$tenantId}, date={$dateString}");
                    }
                } else {
                    throw $e;
                }
            }
        }

        return $calendarDate;
    }

    /**
     * 重複スケジュールをチェック（M1では簡易チェック：同時刻一致のみ）
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

        $query = Schedule::where('tenant_id', $currentTenantId)
            ->where('resident_id', $residentId)
            ->where('calendar_date_id', $calendarDateId)
            ->where('start_time', $startTime); // M1では同時刻一致のみチェック

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        if ($query->exists()) {
            $calendarDate = CalendarDate::findOrFail($calendarDateId);
            throw new ScheduleConflictException(
                residentId: $residentId,
                date: $calendarDate->date->format('Y-m-d'),
                startTime: $startTime,
                endTime: $endTime
            );
        }
    }
}

