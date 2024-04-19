<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // ユーザーをユーザーidで検索
        $user = User::where('username_id', 'admin')->first();

        // ロールを名前で検索
        $role = Role::where('name', 'admin')->first();

        // ユーザーにロールを割り当てる
        if ($user && $role) {
            $user->assignRole($role);
        }
    }
}
