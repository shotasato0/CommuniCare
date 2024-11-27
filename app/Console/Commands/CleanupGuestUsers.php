<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Tenant;

class CleanupGuestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:guest-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete guest users who have not been active for more than 1 hour';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $tenantDomainId = env('TENANT_DOMAIN_ID');
    if (!$tenantDomainId) {
        $this->error('TENANT_DOMAIN_ID が設定されていません。');
        return;
    }

    $tenant = Tenant::whereJsonContains('data->tenant_domain_id', $tenantDomainId)->first();
    if (!$tenant) {
        $this->error("指定されたテナントが見つかりません: {$tenantDomainId}");
        return;
    }

    try {
        // テナントの初期化
        Tenancy::initialize($tenant);
        $this->info("Using database connection: " . \DB::connection()->getDatabaseName());

        // guest_session_id の確認
        if (!\Schema::hasColumn('users', 'guest_session_id')) {
            $this->error("'guest_session_id' カラムが 'users' テーブルに存在しません。");
            return;
        }

        // ゲストユーザー削除
        $deletedCount = User::whereNotNull('guest_session_id')
            ->where('created_at', '<', now()->subHours(1))
            ->delete();

        $this->info("Deleted {$deletedCount} guest users.");
    } catch (\Exception $e) {
        $this->error("エラーが発生しました: " . $e->getMessage());
    } finally {
        // テナントの終了
        Tenancy::end();
    }
}
}
