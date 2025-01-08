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
        // guestdemo テナントだけを取得
        $tenant = Tenant::where('tenant_domain_id', 'guestdemo')->first();

        if (!$tenant) {
            $this->error("テナント 'guestdemo' が見つかりません。");
            return;
        }

        $this->info("テナント処理開始: " . $tenant->business_name);

        try {
            // テナントを初期化
            Tenancy::initialize($tenant);
            $this->info("データベース接続: " . \DB::connection()->getDatabaseName());

            // ゲストユーザー削除処理
            $deletedCount = User::whereNotNull('guest_session_id')
                ->where('created_at', '<', now()->subHours(1))
                ->delete();

            $this->info("テナント [{$tenant->business_name}] のゲストユーザーを削除しました: {$deletedCount} 件");
        } catch (\Exception $e) {
            $this->error("処理中にエラーが発生しました: " . $e->getMessage());
            $this->error("スタックトレース: " . $e->getTraceAsString());
        } finally {
            Tenancy::end();
        }

        $this->info('テナント guestdemo の処理が完了しました。');
    }
}
