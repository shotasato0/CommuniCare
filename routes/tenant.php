<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

// ルートに適用するミドルウェアを指定します。 'web' ミドルウェアとStancl\Tenancyの二つのミドルウェアを適用します。
Route::middleware([
    'web', // Laravelの標準的なウェブアプリケーションのミドルウェアスタック（セッション、CSRF保護などを含む）
    InitializeTenancyByDomain::class, // ドメインによってテナントを初期化するミドルウェア
    PreventAccessFromCentralDomains::class, // 中央ドメインからのアクセスを防ぐミドルウェア
])->group(function () { // ミドルウェアを適用したルートグループを定義します
    // ルートグループ内のルートを定義します
    Route::get('/', function () { // HTTP GETリクエストに応答するルートを定義します（ルートパスは '/'）
        // テナントIDを含むメッセージを返します
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
        // 'tenant' ヘルパー関数を使用して現在のテナントIDを取得し、それを返す文字列に追加します
    });
});
