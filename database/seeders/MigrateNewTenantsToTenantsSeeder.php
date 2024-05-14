<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateNewTenantsToTenantsSeeder extends Seeder
{
    public function run()
    {
        $newTenants = DB::table('new_tenants')->get();

        foreach ($newTenants as $newTenant) {
            DB::table('tenants')->insert([
                'name' => $newTenant->name,
                'domain' => $newTenant->domain,
                'created_at' => $newTenant->created_at,
                'updated_at' => $newTenant->updated_at,
            ]);
        }
    }
}

