<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateNursingHomesToNewTenantsSeeder extends Seeder
{
    public function run()
    {
        $nursingHomes = DB::table('nursing_homes')->get();

        foreach ($nursingHomes as $nursingHome) {
            DB::table('tenants')->insert([
                'name' => $nursingHome->name,
                'domain' => $this->generateUniqueDomain($nursingHome->name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function generateUniqueDomain($name)
    {
        $domain = Str::slug($name, '-') . '.example.com';
        $count = 1;

        while (DB::table('tenants')->where('domain', $domain)->exists()) {
            $domain = Str::slug($name, '-') . $count . '.example.com';
            $count++;
        }

        return $domain;
    }
}
