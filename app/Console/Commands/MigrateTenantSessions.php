<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Tenancy;

class MigrateTenantSessions extends Command
{
    protected $signature = 'tenant:migrate-sessions {tenantId}';
    protected $description = 'Run session migration for a specific tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenantId');

        // テナントの接続を確立
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");
            return;
        }

        tenancy()->initialize($tenant);

        // テナントの接続確認
        $databaseName = $tenant->database;
        config(['database.connections.tenant.database' => $databaseName]);
        \DB::purge('tenant');
        \DB::reconnect('tenant');

        if (!Schema::connection('tenant')->hasTable('sessions')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2024_06_04_202152_create_sessions_table_for_tenants.php',
                '--database' => 'tenant'
            ]);
            $this->info("Session table migrated for tenant ID {$tenantId}.");
        } else {
            $this->info("Session table already exists for tenant ID {$tenantId}.");
        }

        tenancy()->end();
    }
}
