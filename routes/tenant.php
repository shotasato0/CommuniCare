<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantHomeController;
use App\Http\Controllers\Auth\GuestLoginController;

// テナントドメイン限定のルートは、bootstrap/app.php 側の Route::domain() で
// ミドルウェア（InitializeTenancyByDomain 等）を付与して読み込まれる前提。

// テナントのトップページ
Route::get('/', [TenantHomeController::class, 'index'])->name('tenant-home');

// 表示：未ログインでも可（TenantHome.vue を見せる）
Route::get('/guest/login', [TenantHomeController::class, 'index'])->name('guest.login.view');

// 実行：ゲストで入る（中央には置かない）
Route::middleware('guest')->get('/guest/user/login', [GuestLoginController::class, 'loginAsGuest'])->name('guest.user.login');
