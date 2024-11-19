<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 権限を作成 (存在しない場合のみ作成)
        Permission::firstOrCreate(['name' => 'view admin dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'register employees', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view employee list', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'register unit names', 'guard_name' => 'web']);

        // ロールを作成 (存在しない場合のみ作成)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // 管理者ロールに権限を割り当てる (すでに割り当てられている場合はスキップ)
        $adminRole->syncPermissions([
            'view admin dashboard',
            'register employees',
            'view employee list',
            'register unit names',
        ]);
    }
}

