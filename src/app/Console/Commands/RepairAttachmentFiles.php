<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairAttachmentFiles extends Command
{
    protected $signature = 'attachments:repair-files {--tenant=} {--dry-run}';
    protected $description = '中央/テナント間で保存先が不一致の添付ファイルを、テナント側に補正コピーする（dry-run対応）';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');

        $query = Attachment::query()->withoutGlobalScopes();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $attachments = $query->select('id', 'tenant_id', 'file_path')->orderBy('tenant_id')->get();
        if ($attachments->isEmpty()) {
            $this->info('対象の添付が見つかりません');
            return self::SUCCESS;
        }

        $grouped = $attachments->groupBy('tenant_id');
        $fixed = 0; $missing = 0; $skipped = 0;

        foreach ($grouped as $tid => $items) {
            $tenant = Tenant::find($tid);
            if (!$tenant) {
                $this->warn("テナントが見つかりません: {$tid}（skip）");
                $skipped += $items->count();
                continue;
            }

            // テナント初期化
            tenancy()->initialize($tenant);
            $this->line("=== Tenant: {$tid} ({$items->count()} files) ===");

            foreach ($items as $att) {
                $path = $att->file_path;
                $tenantHas = Storage::disk('public')->exists($path);
                if ($tenantHas) {
                    continue; // OK
                }

                // 中央側に一時的に切り替えて存在確認＆取得
                tenancy()->end();
                $centralHas = Storage::disk('public')->exists($path);
                $content = null;
                if ($centralHas) {
                    $content = Storage::disk('public')->get($path);
                }
                // 再度テナントを初期化
                tenancy()->initialize($tenant);

                if (!$centralHas) {
                    $this->warn("[MISSING] id={$att->id} path={$path}");
                    $missing++;
                    continue;
                }

                if ($dryRun) {
                    $this->info("[DRY-RUN] copy central -> tenant: id={$att->id} path={$path}");
                    continue;
                }

                Storage::disk('public')->put($path, $content);
                $this->info("[FIXED] id={$att->id} path={$path}");
                $fixed++;
            }

            // 終了
            tenancy()->end();
        }

        $this->line("=== Summary ===");
        $this->line("fixed={$fixed}, missing={$missing}, skipped={$skipped}");
        return self::SUCCESS;
    }
}

