<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('units')->insert([
            // 任意のデータを配列形式で挿入
            ['name' => '事務所'],
            ['name' => '看護師'],
            ['name' => 'デイサービス'],
            ['name' => 'ショートステイ'],
            ['name' => 'さくら'],
            ['name' => 'つばき'],
            ['name' => 'さつき'],
            ['name' => 'ぼたん']
        ]);
    }
}
