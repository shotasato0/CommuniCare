<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class TenantController extends Controller
{
    public function showRegistrationForm()
    {
        Log::info('テナント登録フォームを表示しています。');
        return inertia('Auth/TenantRegister');
    }

    public function register(Request $request)
    {
        Log::info('テナント登録が開始されました。');

        $validatedData = $request->validate([
            'tenant_name' => 'required|string|max:255',
        ]);

        Log::info('バリデーションが通過しました。', $validatedData);

        // テナントの作成
        $tenant = Tenant::create([
            'name' => $validatedData['tenant_name'],
        ]);

        Log::info('テナントが作成されました: ' . $tenant->id);

        // ドメインの自動生成
        $domain = strtolower(preg_replace('/[^\x20-\x7E]/', '', str_replace(' ', '-', $validatedData['tenant_name']))) . '.localhost';
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
        ]);

        Log::info('ドメインが設定されました: ' . $domain);

        // テナント登録後にそのテナントのデータベースに切り替える
        tenancy()->initialize($tenant);

        Log::info('データベースがテナントに切り替わりました。');

        // ログに現在のデータベース名を出力
        Log::info('現在のデータベース: ' . \DB::connection()->getDatabaseName());

        // テナントIDをセッションに保存
        session(['tenant_id' => $tenant->id]);

        Log::info('テナントIDがセッションに保存されました。');

        // テナント初期化後にリダイレクト
        return redirect()->to('http://' . $domain . '/register'); // リダイレクト先を新しいドメインに設定
    }
}
