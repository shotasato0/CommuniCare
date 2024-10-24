<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 権限を作成 (管理者向けの機能に関連する権限)
        Permission::create(['name' => 'view admin dashboard']);
        Permission::create(['name' => 'register employees']);
        Permission::create(['name' => 'view employee list']);
        Permission::create(['name' => 'register unit names']);

        // ロールを作成
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // 管理者ロールに権限を割り当てる
        $adminRole->givePermissionTo([
            'view admin dashboard',
            'register employees',
            'view employee list',
            'register unit names',
        ]);
    }
}
