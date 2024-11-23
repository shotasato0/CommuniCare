<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Contracts\Tenant;

class SeedTenantRolesAndPermissions
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param TenantCreated $event
     * @return void
     */
    public function handle(TenantCreated $event): void
    {
        // テナントスコープに切り替え
        tenancy()->initialize($event->tenant);

        // RolePermissionSeeder を実行
        Artisan::call('db:seed', [
            '--class' => 'RolePermissionSeeder',
        ]);

        // ログに記録
        logger()->info("Roles and permissions seeded for tenant: {$event->tenant->id}");
    }
}
