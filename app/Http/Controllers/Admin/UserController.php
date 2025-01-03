<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;
use Inertia\Inertia;

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

    public function registerAdmin(Request $request)
    {
        Tenancy::initialize(tenant());

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username_id' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

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
        if (!$adminExists) {
            $user->assignRole('admin'); // 管理者ロールを付与
        } else {
            $user->assignRole('user'); // 一般ユーザーロールを付与
        }

        // 登録後にログインセッションを開始
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', '管理者の登録が完了しました。');
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
    public function transferAdmin(Request $request)
    {
        $validated = $request->validate([
            'new_admin_id' => 'required|exists:users,id',
        ]);

        $newAdmin = User::find($validated['new_admin_id']);
        $currentAdmin = auth()->user();

        // 現在の管理者の権限を削除
        $currentAdmin->removeRole('admin');
        // 新しい管理者の権限を割り当て
        $currentAdmin->assignRole('user');

        // 新しい管理者の権限を割り当て
        $newAdmin->assignRole('admin');

        return redirect()->route('dashboard')->with('success', '管理者権限を移動しました。');
    }
}
