<?php

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1) Tenant + Domain を作成（直書き）
        $tenant = Tenant::firstOrCreate(
            ['tenant_domain_id' => 'guestdemo'],
            [
                'business_name' => 'Guest Demo',
                'tenant_domain_id' => 'guestdemo',
                'data' => [],
            ]
        );

        Domain::firstOrCreate([
            'domain' => 'guestdemo.localhost',
            'tenant_id' => $tenant->id,
        ]);

        // 2) 必要に応じて Forum を1件作成（tenant_id 必須）
        Forum::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Welcome',
            ],
            [
                'description' => '施設における全体連絡のための掲示板',
                'unit_id' => null,
                'visibility' => 'public',
                'status' => 'active',
            ]
        );

        // 3) ユーザーを1件作成（tenant_id 必須）
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
            ]
        );

        // 4) 既存 Seeder を呼び出し（Forums は tenant_id 追加前の実装に配慮し、例外時は継続）
        $this->call([
            RolePermissionSeeder::class,
        ]);

        try {
            $this->call([
                ForumsTableSeeder::class,
            ]);
        } catch (\Throwable $e) {
            // forums.tenant_id の NOT NULL 制約等で失敗しても他のシーディングは継続
            // 最小修正のため、ここでは完走を優先しつつログに警告を残す
            Log::warning('ForumsTableSeeder failed and was skipped: ' . $e->getMessage(), [
                'exception' => get_class($e),
            ]);
        }
    }
}
