<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function showRegisterAdminForm()
    {
        // 管理者が存在するかを確認
        $adminExists = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->exists();

        return inertia('Auth/RegisterAdmin', [
            'adminExists' => $adminExists,
        ]);
    }

    public function registerAdmin(Request $request)
    {
        // 管理者が存在する場合、登録を拒否
        $adminExists = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->exists();

        if ($adminExists) {
            return response()->json([
                'message' => '管理者は既に登録されています。',
            ], 403);
        }

        // バリデーション
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 新しい管理者を作成
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole('admin'); // 管理者ロールを付与

        return redirect()->route('dashboard')->with(['success' => 'ユーザー登録が完了しました。']);
    }
}

