<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Stancl\Tenancy\Database\Models\Tenant;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


app(Schedule::class)->command('cleanup:guest-users')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// attachments GC: 毎日深夜にtempをクリーン
app(Schedule::class)->command('attachments:gc --days=1')
    ->dailyAt('03:10')
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
