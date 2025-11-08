<?php

namespace Database\Seeders;

use App\Models\CalendarDate;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CalendarDatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * 前月〜翌月の3か月分の日付マスタを各テナントに作成
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $today = Carbon::today();
            $startDate = $today->copy()->subMonth()->startOfMonth();
            $endDate = $today->copy()->addMonth()->endOfMonth();

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                CalendarDate::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'date' => $currentDate->format('Y-m-d'),
                    ],
                    [
                        'day_of_week' => $currentDate->dayOfWeek,
                        'is_holiday' => false,
                        'holiday_name' => null,
                    ]
                );

                $currentDate->addDay();
            }
        }
    }
}
