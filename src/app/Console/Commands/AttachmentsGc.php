<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AttachmentsGc extends Command
{
    protected $signature = 'attachments:gc {--tenant=} {--days=1} {--dry-run}';
    protected $description = 'attachmentsの一時領域(temp)と不要生成物をGC。--days=N より古いtempを削除。';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $threshold = now()->subDays($days)->getTimestamp();

        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::query()->get();
        if ($tenants->isEmpty()) {
            $this->info('対象テナントなし');
            return self::SUCCESS;
        }

        $removed = 0; $checked = 0;
        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $this->line("=== Tenant {$tenant->id} ===");

            // temp配下の古いファイルを削除
            $prefix = 'temp/attachments';
            $dirs = Storage::disk('public')->directories('temp');
            if (!in_array('temp/attachments', $dirs, true)) {
                tenancy()->end();
                continue;
            }
            $files = Storage::disk('public')->allFiles($prefix);
            foreach ($files as $file) {
                $checked++;
                $last = Storage::disk('public')->lastModified($file);
                if ($last < $threshold) {
                    if ($dryRun) {
                        $this->info("[DRY-RUN] delete temp: {$file}");
                    } else {
                        Storage::disk('public')->delete($file);
                        $this->info("[DELETED] temp: {$file}");
                    }
                    $removed++;
                }
            }
            tenancy()->end();
        }

        $this->line("=== Summary === checked={$checked}, removed={$removed}");
        return self::SUCCESS;
    }
}

