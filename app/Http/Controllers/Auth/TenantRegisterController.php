<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Inertia\Inertia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class TenantRegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return Inertia::render('Auth/TenantRegister');
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'business_name' => 'required|string|max:255',
            'tenant_domain_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9]+$/',
                'unique:tenants,tenant_domain_id',
            ],
        ]);

        // ドメイン名を生成
        $domain = strtolower(preg_replace('/[^\x20-\x7E]/', '', str_replace(' ', '-', $validatedData['tenant_domain_id']))) . '.localhost';

        // ドメインの重複チェック
        if (Domain::where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'tenant_domain_id' => 'このドメインは既に使用されています。',
            ]);
        }

        // テナントの作成
        $tenant = Tenant::create([
            'business_name' => $validatedData['business_name'],
            'tenant_domain_id' => $validatedData['tenant_domain_id'],
        ]);

        // ドメインの登録
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
        ]);

        // テナント登録後にそのテナントのデータベースに切り替える
        tenancy()->initialize($tenant);

        // セッションのドメインを動的に設定
        Config::set('session.domain', $domain);

        // クッキーの設定
        $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $domain, false, true, false, 'Lax');
        Cookie::queue($cookie);

        // テナントIDをセッションに保存
        session(['tenant_id' => $tenant->id]);

        // ドメイン名をセッションに保存
        session(['tenant_domain' => $domain]);

        // テナント初期化後にリダイレクト
        return Inertia::location('http://' . $domain . '/home');
    }
}
