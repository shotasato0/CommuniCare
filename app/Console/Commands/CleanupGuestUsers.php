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
        $this->info("設定されているTENANT_DOMAIN_ID: " . ($tenantDomainId ?? 'null'));

        if (!$tenantDomainId) {
            $this->error('TENANT_DOMAIN_ID が設定されていません。');
            return;
        }

        // テナントの検索処理をデバッグ
        $tenant = Tenant::whereJsonContains('data->tenant_domain_id', $tenantDomainId)->first();
        $this->info("検索クエリ結果: " . ($tenant ? "テナントが見つかりました" : "テナントが見つかりません"));
        
        if ($tenant) {
            $this->info("テナントデータ: " . json_encode($tenant->data));
        }

        if (!$tenant) {
            $this->error("指定されたテナントが見つかりません: {$tenantDomainId}");
            return;
        }

        try {
            // テナントの初期化
            Tenancy::initialize($tenant);
            $this->info("データベース接続: " . \DB::connection()->getDatabaseName());

            // 削除対象のユーザー数を事前に確認
            $targetUsers = User::whereNotNull('guest_session_id')
                ->where('created_at', '<', now()->subHours(1))
                ->get();
            
            $this->info("削除対象ユーザー数: " . $targetUsers->count());
            $this->info("対象ユーザー情報: " . json_encode($targetUsers->toArray()));

            $deletedCount = User::whereNotNull('guest_session_id')
                ->where('created_at', '<', now()->subHours(1))
                ->delete();

            $this->info("削除完了。削除数: {$deletedCount}");
        } catch (\Exception $e) {
            $this->error("エラーが発生しました: " . $e->getMessage());
            $this->error("スタックトレース: " . $e->getTraceAsString());
        } finally {
            Tenancy::end();
        }
    }
}
