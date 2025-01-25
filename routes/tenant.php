<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\TenantHomeController;

// ルートに適用するミドルウェアを指定します。 'web' ミドルウェアとStancl\Tenancyの二つのミドルウェアを適用します。
Route::middleware([
    'web', // Laravelの標準的なウェブアプリケーションのミドルウェアスタック（セッション、CSRF保護などを含む）
    InitializeTenancyByDomain::class, // ドメインによってテナントを初期化するミドルウェア
    PreventAccessFromCentralDomains::class, // 中央ドメインからのアクセスを防ぐミドルウェア
])->group(function () { // ミドルウェアを適用したルートグループを定義します
    // テナントのトップページへのルート
    Route::get('/', [TenantHomeController::class, 'index'])->name('tenant-home');
});
