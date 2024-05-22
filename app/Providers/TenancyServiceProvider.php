<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\JobPipeline;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenancyServiceProvider extends ServiceProvider
{
    public function register()
    {
        // その他の設定
    }

    public function boot()
    {
        // Stancl\Tenancyの設定
        Tenancy::event(TenantCreated::class, function (TenantCreated $event) {
            JobPipeline::make([
                CreateDatabase::class,
                MigrateDatabase::class,
            ])->send(function (TenantCreated $event) {
                return $event->tenant;
            })->shouldBeQueued(false);
        });

        // カスタムイベントリスナーを登録してデータベースの作成を確認
        Tenancy::event(TenantCreated::class, function (TenantCreated $event) {
            $databaseName = 'tenant_' . $event->tenant->id;
            DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

            config([
                'database.connections.tenant' => [
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => $databaseName,
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ],
            ]);

            DB::purge('tenant');
            DB::reconnect('tenant');
            tenancy()->initialize($event->tenant);

            // テナント用のマイグレーションを実行
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }
}
