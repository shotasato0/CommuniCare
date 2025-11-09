<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Support\Facades\Auth;

class Schedule extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'calendar_date_id',
        'resident_id',
        'schedule_name',
        'schedule_type_id',
        'start_time',
        'end_time',
        'memo',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    /**
     * 日付マスタとのリレーション
     */
    public function calendarDate()
    {
        return $this->belongsTo(CalendarDate::class);
    }

    /**
     * 利用者とのリレーション
     */
    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * 種別とのリレーション
     */
    public function scheduleType()
    {
        return $this->belongsTo(ScheduleType::class);
    }

    /**
     * 作成者とのリレーション
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 開始時刻が終了時刻より前かチェック
     */
    public function validateTimeRange(): bool
    {
        return $this->start_time < $this->end_time;
    }

    /**
     * 現在のテナントのスケジュールのみ取得するスコープ
     */
    public function scopeForCurrentTenant($query)
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            return $query->where('tenant_id', Auth::user()->tenant_id);
        }
        return $query;
    }
}
