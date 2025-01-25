<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TenantLoginRequest;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Inertia\Inertia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class TenantLoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/TenantLogin');
    }

    public function store(TenantLoginRequest $request): Response|RedirectResponse
    {
        // バリデーション済みのデータを取得
    $credentials = $request->validated();

    // テナントを取得
    $tenant = Tenant::whereJsonContains('data->business_name', $credentials['business_name'])
        ->whereJsonContains('data->tenant_domain_id', $credentials['tenant_domain_id'])
        ->first();

    if (!$tenant) {
        return back()->withErrors([
            'business_name' => '入力された情報が一致しません。',
            'tenant_domain_id' => '入力された情報が一致しません。',
        ]);
    }

    // テナントのドメインを取得
    $domain = Domain::where('tenant_id', $tenant->id)->first();
    if (!$domain) {
        return back()->withErrors(['tenant_domain_id' => '対応するドメインが見つかりません。']);
    }

    // テナントのデータベースを初期化
    tenancy()->initialize($tenant);

    // セッションのドメインを動的に設定
    Config::set('session.domain', $domain->domain);

    // クッキーの設定
    $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $domain->domain, false, true, false, 'Lax');
    Cookie::queue($cookie);

    // テナントIDをセッションに保存
    session(['tenant_id' => $tenant->id]);

    // セッションのリジェネレーション
    $request->session()->regenerate();

    // テナント初期化後にInertiaでリダイレクト
    return Inertia::location('http://' . $domain->domain . '/');
}
}
