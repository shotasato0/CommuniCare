<?php

namespace Database\Seeders;

use App\Models\ScheduleType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // デフォルトのスケジュール種別定義
        $defaultTypes = [
            [
                'name' => '入浴',
                'color' => '#3B82F6',
                'description' => '通常の入浴',
                'sort_order' => 1,
            ],
            [
                'name' => 'シャワー',
                'color' => '#10B981',
                'description' => 'シャワー浴',
                'sort_order' => 2,
            ],
            [
                'name' => '部分浴',
                'color' => '#F59E0B',
                'description' => '部分的な入浴',
                'sort_order' => 3,
            ],
        ];

        // 全テナントに対してデフォルト種別を作成
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($defaultTypes as $typeData) {
                ScheduleType::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $typeData['name'],
                    ],
                    [
                        'color' => $typeData['color'],
                        'description' => $typeData['description'],
                        'sort_order' => $typeData['sort_order'],
                    ]
                );
            }
        }
    }
}
