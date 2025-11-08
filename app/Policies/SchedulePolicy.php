<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Schedule;

class SchedulePolicy
{
    /**
     * テナント境界チェック
     *
     * @param User $user
     * @param Schedule $schedule
     * @return bool
     */
    private function checkTenantBoundary(User $user, Schedule $schedule): bool
    {
        return $schedule->tenant_id === $user->tenant_id;
    }

    /**
     * スケジュール一覧閲覧権限をチェック
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('schedules.view');
    }

    /**
     * スケジュール閲覧権限をチェック
     *
     * @param User $user
     * @param Schedule $schedule
     * @return bool
     */
    public function view(User $user, Schedule $schedule): bool
    {
        // テナント境界チェック（必須）
        if (!$this->checkTenantBoundary($user, $schedule)) {
            return false;
        }

        // 権限チェック
        return $user->hasPermissionTo('schedules.view');
    }

    /**
     * スケジュール作成権限をチェック
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('schedules.create');
    }

    /**
     * スケジュール更新権限をチェック
     *
     * @param User $user
     * @param Schedule $schedule
     * @return bool
     */
    public function update(User $user, Schedule $schedule): bool
    {
        // テナント境界チェック（必須）
        if (!$this->checkTenantBoundary($user, $schedule)) {
            return false;
        }

        // 権限チェック
        if (!$user->hasPermissionTo('schedules.update')) {
            return false;
        }

        // 一般ユーザーは自分が作成したスケジュールのみ更新可能
        if (!$user->hasRole('admin') && $schedule->created_by !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * スケジュール削除権限をチェック
     *
     * @param User $user
     * @param Schedule $schedule
     * @return bool
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        // updateと同じロジック
        return $this->update($user, $schedule);
    }
}
