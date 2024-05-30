<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUserTenantIdsSeeder extends Seeder
{
    public function run()
    {
        $newTenants = DB::table('new_tenants')->get();

        foreach ($newTenants as $newTenant) {
            $oldTenantId = DB::table('nursing_homes')->where('name', $newTenant->name)->value('id');
            $newTenantId = DB::table('tenants')->where('domain', $newTenant->domain)->value('id');

            DB::table('users')->where('tenant_id', $oldTenantId)->update(['tenant_id' => $newTenantId]);
        }
    }
}