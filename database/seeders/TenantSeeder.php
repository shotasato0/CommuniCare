<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run()
    {
        // データベースに存在しない場合のみテナントを作成
        Tenant::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Example Tenant',
                'domain' => 'localhost',
                'database' => 'default_database'
            ]
        );
    }
}
