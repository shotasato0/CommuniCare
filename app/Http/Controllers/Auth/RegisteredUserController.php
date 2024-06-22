<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;

class RegisteredUserController extends Controller
{
    protected $tenancy;

    public function __construct(Tenancy $tenancy)
    {
        $this->tenancy = $tenancy;
    }

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username_id' => ['required', 'string', 'max:255', 'unique:users,username_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($request->boolean('is_admin')) {
            $rules['tenant_name'] = ['required', 'string', 'max:255', 'unique:tenants,name'];
        }

        $request->validate($rules);

        $userData = [
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
        ];

        if ($request->boolean('is_admin')) {
            $tenantName = $request->tenant_name;
            $domain = $this->generateUniqueDomain($tenantName);
            $databaseName = 'tenant_' . transliterator_transliterate('Any-Latin; Latin-ASCII', $tenantName);
            $databaseName = preg_replace('/[^A-Za-z0-9_]/', '_', $databaseName);

            // テナントの作成
            $tenant = Tenant::create([
                'name' => $tenantName,
                'domain' => $domain,
                'database' => $databaseName,
            ]);

            // テナントデータベースの作成
            DB::statement("CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            Config::set("database.connections.tenant.database", $databaseName);

            // テナント用のマイグレーションを実行
            DB::purge('tenant');
            DB::reconnect('tenant');
            Log::info("Migrating tenant database: $databaseName");
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // テナントのコンテキストでユーザーを作成
            $this->tenancy->initialize($tenant);
            Log::info("Creating user in tenant database: $databaseName");

            // テナントデータベースにテナントのレコードを追加
            DB::connection('tenant')->table('tenants')->insert([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // テナントデータベースにユーザーを追加
            DB::connection('tenant')->table('users')->insert([
                'name' => $request->name,
                'username_id' => $request->username_id,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ユーザーモデルを取得
            $user = DB::connection('tenant')->table('users')->where('username_id', $request->username_id)->first();
            $userModel = new User((array) $user);
            $userModel->setConnection('tenant');
            $userModel->assignRole('admin');

            // テナント初期化後にデフォルトデータベースを切り替える
            $this->tenancy->initialize($tenant);
            Config::set("database.connections.mysql.database", $databaseName);

            Auth::login($userModel);
        } else {
            $user = User::create($userData);
            Auth::login($user);
        }

        return redirect(route('dashboard'))->with('success', '新しいユーザーが正常に登録されました。');
    }

    private function generateUniqueDomain(string $tenantName): string
    {
        $baseDomain = Str::slug($tenantName) . '.example.com';
        $domain = $baseDomain;
        $counter = 1;

        while (Tenant::where('domain', $domain)->exists()) {
            $domain = Str::slug($tenantName) . $counter . '.example.com';
            $counter++;
        }

        return $domain;
    }
}
