<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Inertia\Inertia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class TenantController extends Controller
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

        // テナントの作成
        $tenant = Tenant::create([
            'business_name' => $validatedData['business_name'],
            'tenant_domain_id' => $validatedData['tenant_domain_id'],
        ]);

        // ドメインの自動生成
        $domain = strtolower(preg_replace('/[^\x20-\x7E]/', '', str_replace(' ', '-', $validatedData['tenant_domain_id']))) . '.localhost';
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

        // テナント初期化後にリダイレクト
        return Inertia::location('http://' . $domain . '/home');
    }

    public function showLoginForm()
    {
        return Inertia::render('Auth/TenantLogin');
    }
}
