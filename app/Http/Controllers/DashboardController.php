<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 管理者かどうかのフラグを設定
        $isAdmin = $user ? $user->hasRole('admin') : false;

        // ダッシュボードビューをレンダリングし、ユーザー情報と管理者フラグを渡す
        return inertia('Dashboard', [
            'auth' => ['user' => $user ? $user->load('roles') : null],
            'isAdmin' => $isAdmin,
        ]);
    }
}
