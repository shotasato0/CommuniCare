<?php

use Tenancy\Affects\Connections\Events\Resolving;
use Tenancy\Hooks\Database\Events\Drivers\Configuring;
use Tenancy\Identification\Contracts\Tenant;
use Tenancy\Lifecycle\ConfigurableHooks;
use Tenancy\Lifecycle\Events\Updated;
use Tenancy\Tenant\Events\Created;
use Tenancy\Tenant\Events\Deleted;

class TenancyServiceProvider extends ServiceProvider
{
    public function register()
    {
        // その他の設定
    }

    public function boot()
    {
        // その他の設定

        ConfigurableHooks::register(
            Configuring::class,
            function (Configuring $event) {
                if ($event->tenant instanceof Tenant) {
                    $event->useConnection('tenant', [
                        'driver' => 'mysql',
                        'host' => env('TENANCY_DB_HOST', '127.0.0.1'),
                        'port' => env('TENANCY_DB_PORT', '3306'),
                        'database' => 'tenant_' . $event->tenant->getTenantKey(),
                        'username' => env('TENANCY_DB_USERNAME', 'root'),
                        'password' => env('TENANCY_DB_PASSWORD', ''),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                    ]);
                }
            }
        );

        ConfigurableHooks::register(
            Updated::class,
            function (Updated $event) {
                if ($event->tenant instanceof Tenant) {
                    // テナントのデータベースを作成
                    DB::statement('CREATE DATABASE IF NOT EXISTS tenant_' . $event->tenant->getTenantKey());
                }
            }
        );

        ConfigurableHooks::register(
            Deleted::class,
            function (Deleted $event) {
                if ($event->tenant instanceof Tenant) {
                    // テナントのデータベースを削除
                    DB::statement('DROP DATABASE IF EXISTS tenant_' . $event->tenant->getTenantKey());
                }
            }
        );
    }
}
