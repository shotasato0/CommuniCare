<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'username_id' => 'admin',
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'defaultpassword')),
            'email_verified_at' => now(),
        ]);
    }
}
