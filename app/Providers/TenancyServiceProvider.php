<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\JobPipeline;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenancyServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Tenancy::event(TenantCreated::class, function (TenantCreated $event) {
            $databaseName = 'tenant_' . $event->tenant->id;

            // データベースの作成
            DB::statement("CREATE DATABASE IF NOT EXISTS `$databaseName`");

            // テナント用のマイグレーションを実行
            tenancy()->initialize($event->tenant);
            Artisan::call('tenants:migrate', [
                '--tenants' => [$event->tenant->id],
                '--path' => 'database/migrations/tenant',
            ]);
        });
    }
}
