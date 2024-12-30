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
use Illuminate\Support\Facades\DB;

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

        // 環境に応じてベースドメインを設定
        $baseDomain = app()->environment('production') ? 'communi-care.jp' : 'localhost';

        // ドメイン名を生成
        $domain = strtolower(preg_replace('/[^\x20-\x7E]/', '', str_replace(' ', '-', $validatedData['tenant_domain_id']))) . '.' . $baseDomain;

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

        // テナントDBにテナント情報を複製
        $tenant->run(function () use ($tenant) {
            DB::table('tenant_info')->insert([
                'id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'tenant_domain_id' => $tenant->tenant_domain_id,
                'data' => null,  // 必要に応じてデータを追加
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // セッションのドメインを動的に設定
        $sessionDomain = app()->environment('production') ? '.communi-care.jp' : '.localhost';
        Config::set('session.domain', $sessionDomain);

        // クッキーの設定
        $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $sessionDomain, false, true, false, 'Lax');
        Cookie::queue($cookie);

        // テナントIDをセッションに保存
        session(['tenant_id' => $tenant->id]);

        // テナント初期化後にリダイレクト
        return Inertia::location('http://' . $domain . '/home');
    }
}
