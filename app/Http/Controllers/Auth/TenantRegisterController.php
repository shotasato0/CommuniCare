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
    /**
     * テナント登録フォームの表示
     */
    public function showRegistrationForm()
    {
        return Inertia::render('Auth/TenantRegister');
    }

    /**
     * テナントの登録
     */
    public function register(Request $request)
    {
        // バリデーション
        $validatedData = $request->validate([
            'business_name' => 'required|string|max:255',
            'tenant_domain_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9]+$/',  // 英数字のみ許可
                'unique:tenants,tenant_domain_id',  // 重複チェック
            ],
        ]);

        // ドメイン名の生成
        $baseDomain = app()->environment('production') ? 'communi-care.jp' : 'localhost';
        $domain = strtolower($validatedData['tenant_domain_id']) . '.' . $baseDomain;

        try {
            DB::beginTransaction();

            // テナントの作成
            $tenant = new Tenant();
            $tenant->forceFill([
                'business_name' => $validatedData['business_name'],
                'tenant_domain_id' => $validatedData['tenant_domain_id'],
            ]);
            $tenant->save();

            // テナントドメインの作成
            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $domain,
            ]);

            // tenant_infoテーブルへの登録（主にビュー表示用）
            DB::table('tenant_info')->insert([
                'id' => $tenant->id,
                'business_name' => $tenant->business_name,
                'tenant_domain_id' => $tenant->tenant_domain_id,
                'data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // セッションとクッキーの設定
        session(['tenant_id' => $tenant->id]);
        $sessionDomain = app()->environment('production') ? '.communi-care.jp' : '.localhost';
        Config::set('session.domain', $sessionDomain);
        Cookie::queue(Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $sessionDomain, false, true, false, 'Lax'));

        // テナントのホームページにリダイレクト
        return Inertia::location('http://' . $domain . '/home');
    }

}
