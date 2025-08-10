<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use App\Http\Requests\Admin\AdminRegisterRequest;
use App\Http\Requests\Admin\AdminTransferRequest;

class UserController extends Controller
{
    public function showRegisterAdminForm()
    {
        // テナントIDを取得
        $tenantId = tenant('id');

        // このテナントに管理者が存在するかを確認
        $adminExists = User::role('admin')
            ->where('tenant_id', $tenantId)
            ->exists();

        return inertia('Auth/RegisterAdmin', [
            'adminExists' => $adminExists,
        ]);
    }

    public function registerAdmin(AdminRegisterRequest $request)
{
    Tenancy::initialize(tenant());

    $validated = $request->validated();
    $tenantId = tenant('id');

    // このテナントに管理者が存在するかを確認
    $adminExists = User::role('admin')
        ->where('tenant_id', $tenantId)
        ->exists();

    // 新しいユーザーを作成
    $user = User::create([
        'name' => $validated['name'],
        'username_id' => $validated['username_id'],
        'password' => bcrypt($validated['password']),
        'tenant_id' => $tenantId,
    ]);

    // ロール割り当て
    $adminRole = Role::findByName('admin');
    $userRole = Role::findByName('user');

    if (!$adminExists) {
        $user->assignRole($adminRole); // 管理者ロールを付与
    } else {
        $user->assignRole($userRole); // 一般ユーザーロールを付与
    }

    // ログイン
    Auth::login($user);
    return redirect()->route('users.index')->with('success', '管理者の登録が完了しました。');
}

    // テナントの管理者を移動するためのフォームを表示
    public function showTransferAdminForm()
    {
        // 現在のテナントに所属するユーザーを取得
        $users = User::where('tenant_id', tenant('id'))->get();

        // 現在の管理者を取得
        $currentAdmin = User::role('admin')
            ->where('tenant_id', tenant('id'))
            ->first();

        return inertia('Admin/TransferAdmin', [
            'users' => $users,
            'currentAdminId' => $currentAdmin?->id, // 現在の管理者のIDを渡す
        ]);
    }

    // 管理者権限を移動
    public function transferAdmin(AdminTransferRequest $request)
    {
        $validated = $request->validated();

        $newAdmin = User::find($validated['new_admin_id']);
        $currentAdmin = Auth::user();

        // 管理者ロールと一般ユーザーロールを取得
        $adminRole = Role::findByName('admin');
        $userRole = Role::findByName('user');

        // トランザクションを使って安全に権限を移動
        // 注: syncRoles()の使用が推奨されるが、現在の環境では認識されないため、
        // 直接DBクエリを使用してSpatie Permissionの内部テーブルを操作
        DB::transaction(function () use ($currentAdmin, $newAdmin, $adminRole, $userRole) {
            // 現在の管理者から管理者権限を削除
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $currentAdmin->id)
                ->where('role_id', $adminRole->id)
                ->delete();

            // 現在の管理者にユーザー権限を付与（既にない場合）
            $existsUserRole = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $currentAdmin->id)
                ->where('role_id', $userRole->id)
                ->exists();

            if (!$existsUserRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $userRole->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $currentAdmin->id,
                ]);
            }

            // 新しい管理者からユーザー権限を削除
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $newAdmin->id)
                ->where('role_id', $userRole->id)
                ->delete();

            // 新しい管理者に管理者権限を付与
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRole->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $newAdmin->id,
            ]);
        });

        return redirect()->route('users.index')->with('success', '管理者権限を移動しました。');
    }
}
