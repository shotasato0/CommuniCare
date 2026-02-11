<?php

namespace App\Exceptions\Custom;

use Exception;

/**
 * スケジュール重複例外
 * 
 * 入浴スケジュール作成・更新時に、同一日付・同一利用者・同一時間帯の
 * スケジュールが既に存在する場合に発生
 */
class ScheduleConflictException extends Exception
{
    /**
     * 利用者ID
     */
    public readonly int $residentId;

    /**
     * 日付（YYYY-MM-DD形式）
     */
    public readonly string $date;

    /**
     * 開始時刻（HH:MM形式）
     */
    public readonly string $startTime;

    /**
     * 終了時刻（HH:MM形式）
     */
    public readonly string $endTime;

    /**
     * 競合しているスケジュールID（存在する場合）
     */
    public readonly ?int $conflictingScheduleId;

    public function __construct(
        int $residentId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $conflictingScheduleId = null,
        string $message = null,
        int $code = 409,
        Exception $previous = null
    ) {
        $this->residentId = $residentId;
        $this->date = $date;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->conflictingScheduleId = $conflictingScheduleId;

        $message = $message ?: sprintf(
            'スケジュール重複エラー: 利用者[ID:%d]の%s %s〜%sに既にスケジュールが存在します',
            $residentId,
            $date,
            $startTime,
            $endTime
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * ログ出力用の詳細情報を取得
     */
    public function getLogContext(): array
    {
        return [
            'exception_type' => 'schedule_conflict',
            'resident_id' => $this->residentId,
            'date' => $this->date,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'conflicting_schedule_id' => $this->conflictingScheduleId,
        ];
    }

    /**
     * ユーザー向けの安全なエラーメッセージを取得
     */
    public function getUserMessage(): string
    {
        return sprintf(
            '指定された時間帯（%s %s〜%s）に既にスケジュールが登録されています。',
            $this->date,
            $this->startTime,
            $this->endTime
        );
    }
}

