<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CalendarDate extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'date',
        'day_of_week',
        'is_holiday',
        'holiday_name',
    ];

    protected $casts = [
        'date' => 'date',
        'day_of_week' => 'integer',
        'is_holiday' => 'boolean',
    ];

    /**
     * スケジュールとのリレーション
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * 曜日ラベルを取得
     */
    public function getWeekdayLabelAttribute(): string
    {
        $labels = ['日', '月', '火', '水', '木', '金', '土'];
        return $labels[$this->day_of_week] ?? '';
    }

    /**
     * 祝日かどうかを判定
     */
    public function isHoliday(): bool
    {
        return $this->is_holiday;
    }
}
