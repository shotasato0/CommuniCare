<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('forums')->insert([
            'name' => 'Welcome',
            'description' => '施設における全体連絡のための掲示板',
            'unit_id' => null,  // どのユニットにも属さないようにNULLに設定
            'visibility' => 'public',  // デフォルトを上書きして明示的に設定
            'status' => 'active',  // デフォルトを上書きして明示的に設定
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
