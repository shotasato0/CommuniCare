<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

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
        $deletedCount = User::whereNotNull('guest_session_id')
            ->where('created_at', '<', now()->subHours(1))
            ->delete();

        $this->info("Deleted {$deletedCount} guest users.");
    }
}
