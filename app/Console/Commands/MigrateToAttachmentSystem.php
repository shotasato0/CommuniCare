<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Services\AttachmentService;
use Exception;

class MigrateToAttachmentSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:to-attachment 
                          {--table= : 特定テーブルのみ移行 (posts, comments, users)}
                          {--batch-size=100 : バッチ処理のサイズ}
                          {--dry-run : 実際の移行は行わず、確認のみ実行}
                          {--force : 本番環境でも強制実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '既存のimg/iconフィールドから新Attachmentシステムへデータ移行（3テーブル対応: posts.img, comments.img, users.icon）';

    private AttachmentService $attachmentService;
    private array $migrationStats = [];
    private string $logPrefix;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->attachmentService = new AttachmentService();
        $this->logPrefix = '[AttachmentMigration]';
        
        $this->info("🚀 Attachmentシステム移行開始");
        $this->info("対象: posts.img, comments.img, users.icon → attachments");
        
        try {
            // 安全性チェック
            if (!$this->validateEnvironment()) {
                return 1;
            }
            
            // 移行統計初期化
            $this->initializeStats();
            
            // バッチサイズ取得
            $batchSize = (int) $this->option('batch-size');
            $isDryRun = $this->option('dry-run');
            $targetTable = $this->option('table');
            
            if ($isDryRun) {
                $this->warn("🔍 DRY RUN MODE: 実際の移行は行いません");
            }
            
            // テーブル別移行実行
            if (!$targetTable || $targetTable === 'posts') {
                $this->migratePostsTable($batchSize, $isDryRun);
            }
            
            if (!$targetTable || $targetTable === 'comments') {
                $this->migrateCommentsTable($batchSize, $isDryRun);
            }
            
            if (!$targetTable || $targetTable === 'users') {
                $this->migrateUsersTable($batchSize, $isDryRun);
            }
            
            // 移行結果レポート
            $this->displayMigrationReport();
            
            if (!$isDryRun) {
                $this->info("✅ 移行完了！");
            }
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("❌ 移行エラー: " . $e->getMessage());
            $this->logError("Migration failed", $e);
            return 1;
        }
    }

    /**
     * 環境の安全性チェック
     */
    private function validateEnvironment(): bool
    {
        $environment = app()->environment();
        
        if ($environment === 'production' && !$this->option('force')) {
            $this->error("⚠️  本番環境での実行には --force オプションが必要です");
            $this->error("実行前に必ずデータベースのバックアップを取得してください");
            return false;
        }
        
        if ($environment === 'production') {
            $this->warn("🚨 本番環境での実行中...");
            
            if (!$this->confirm('データベースのバックアップは取得済みですか？')) {
                $this->error("バックアップを取得してから再実行してください");
                return false;
            }
        }
        
        $this->info("📍 実行環境: {$environment}");
        return true;
    }

    /**
     * 移行統計初期化
     */
    private function initializeStats(): void
    {
        $this->migrationStats = [
            'posts' => ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'errors' => 0],
            'comments' => ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'errors' => 0],
            'users' => ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'errors' => 0],
        ];
    }

    /**
     * 移行結果レポート表示
     */
    private function displayMigrationReport(): void
    {
        $this->info("\n📊 移行結果レポート");
        $this->table(
            ['テーブル', '総件数', '移行済み', 'スキップ', 'エラー'],
            [
                [
                    'posts',
                    $this->migrationStats['posts']['total'],
                    $this->migrationStats['posts']['migrated'],
                    $this->migrationStats['posts']['skipped'],
                    $this->migrationStats['posts']['errors']
                ],
                [
                    'comments',
                    $this->migrationStats['comments']['total'],
                    $this->migrationStats['comments']['migrated'],
                    $this->migrationStats['comments']['skipped'],
                    $this->migrationStats['comments']['errors']
                ],
                [
                    'users',
                    $this->migrationStats['users']['total'],
                    $this->migrationStats['users']['migrated'],
                    $this->migrationStats['users']['skipped'],
                    $this->migrationStats['users']['errors']
                ]
            ]
        );
        
        // ログに記録
        $this->logInfo("Migration completed", $this->migrationStats);
    }

    /**
     * ログ記録（Info）
     */
    private function logInfo(string $message, array $context = []): void
    {
        $logMessage = $this->logPrefix . ' ' . $message;
        Log::info($logMessage, $context);
    }

    /**
     * ログ記録（Error）
     */
    private function logError(string $message, Exception $exception = null): void
    {
        $logMessage = $this->logPrefix . ' ' . $message;
        $context = $exception ? [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ] : [];
        
        Log::error($logMessage, $context);
    }

    /**
     * プログレスバー作成
     */
    private function createProgressBar(int $total, string $label): \Symfony\Component\Console\Helper\ProgressBar
    {
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% | {$label}");
        return $bar;
    }

    // 以下、個別テーブル移行メソッドは次のステップで実装
    private function migratePostsTable(int $batchSize, bool $isDryRun): void
    {
        $this->info("📝 posts.img → attachments 移行準備中...");
        // 次のステップで実装
    }

    private function migrateCommentsTable(int $batchSize, bool $isDryRun): void
    {
        $this->info("💬 comments.img → attachments 移行準備中...");
        // 次のステップで実装
    }

    private function migrateUsersTable(int $batchSize, bool $isDryRun): void
    {
        $this->info("👤 users.icon → attachments 移行準備中...");
        // 次のステップで実装
    }
}
