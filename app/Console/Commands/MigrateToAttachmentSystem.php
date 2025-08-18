<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $this->info("📝 posts.img → attachments 移行開始...");
        
        // 画像付き投稿数を取得
        $totalPosts = Post::whereNotNull('img')
                         ->where('img', '!=', '')
                         ->count();
        
        if ($totalPosts === 0) {
            $this->info("   📋 移行対象の投稿画像が見つかりません");
            return;
        }
        
        $this->migrationStats['posts']['total'] = $totalPosts;
        $this->info("   📊 移行対象: {$totalPosts}件の投稿画像");
        
        $progressBar = $this->createProgressBar($totalPosts, 'posts.img');
        $progressBar->start();
        
        // バッチ処理で投稿を処理
        Post::whereNotNull('img')
            ->where('img', '!=', '')
            ->chunk($batchSize, function ($posts) use ($isDryRun, $progressBar) {
                foreach ($posts as $post) {
                    try {
                        if ($isDryRun) {
                            // Dry Run: 処理をシミュレート
                            $this->migrationStats['posts']['migrated']++;
                        } else {
                            // 実際の移行実行
                            if ($this->migratePostImage($post)) {
                                $this->migrationStats['posts']['migrated']++;
                            } else {
                                $this->migrationStats['posts']['skipped']++;
                            }
                        }
                        
                        $progressBar->advance();
                        
                    } catch (Exception $e) {
                        $this->migrationStats['posts']['errors']++;
                        $this->logError("Post image migration failed for post {$post->id}", $e);
                        $progressBar->advance();
                    }
                }
            });
        
        $progressBar->finish();
        $this->newLine(2);
        
        $migrated = $this->migrationStats['posts']['migrated'];
        $errors = $this->migrationStats['posts']['errors'];
        
        if ($isDryRun) {
            $this->info("   ✅ DRY RUN: {$migrated}件の投稿画像が移行対象です");
        } else {
            $this->info("   ✅ {$migrated}件の投稿画像を移行完了");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors}件でエラーが発生しました");
            }
        }
    }

    /**
     * 個別投稿画像の移行処理
     */
    private function migratePostImage(Post $post): bool
    {
        try {
            // 既存のAttachmentをチェック（重複回避）
            if ($post->attachments()->where('file_type', 'image')->exists()) {
                return false; // すでに移行済み
            }
            
            // 投稿画像ファイルの存在確認
            $imgPath = $post->img;
            
            if (!$imgPath || !Storage::disk('public')->exists($imgPath)) {
                return false; // ファイルが存在しない
            }
            
            // ファイル情報取得
            $fullPath = storage_path('app/public/' . $imgPath);
            $originalName = basename($imgPath);
            $fileSize = filesize($fullPath);
            $mimeType = mime_content_type($fullPath);
            
            // ファイル拡張子とタイプ判定
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                return false; // サポートされていない画像形式
            }
            
            // 安全なファイル名生成
            $safeFileName = $this->generateSafeFileName($originalName, $extension);
            $newPath = 'attachments/images/' . $safeFileName;
            
            // ファイルハッシュ生成
            $fileHash = hash_file('sha256', $fullPath);
            
            // トランザクション内で処理
            DB::transaction(function () use ($post, $originalName, $safeFileName, $newPath, $fileSize, $mimeType, $fileHash, $imgPath) {
                // 新しい場所にファイルコピー
                Storage::disk('public')->copy($imgPath, $newPath);
                
                // Attachmentレコード作成
                Attachment::create([
                    'attachable_type' => 'App\Models\Post',
                    'attachable_id' => $post->id,
                    'original_name' => $originalName,
                    'file_name' => $safeFileName,
                    'file_path' => $newPath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'file_type' => 'image',
                    'tenant_id' => $post->tenant_id,
                    'uploaded_by' => $post->user_id,
                    'hash' => $fileHash,
                    'is_safe' => true
                ]);
            });
            
            $this->logInfo("Post image migrated successfully", [
                'post_id' => $post->id,
                'original_path' => $imgPath,
                'new_path' => $newPath
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError("Failed to migrate post image for post {$post->id}", $e);
            throw $e;
        }
    }

    private function migrateCommentsTable(int $batchSize, bool $isDryRun): void
    {
        $this->info("💬 comments.img → attachments 移行準備中...");
        // 次のステップで実装
    }

    private function migrateUsersTable(int $batchSize, bool $isDryRun): void
    {
        $this->info("👤 users.icon → attachments 移行開始...");
        
        // アイコン付きユーザー数を取得
        $totalUsers = User::whereNotNull('icon')
                         ->where('icon', '!=', '')
                         ->count();
        
        if ($totalUsers === 0) {
            $this->info("   📋 移行対象のユーザーアイコンが見つかりません");
            return;
        }
        
        $this->migrationStats['users']['total'] = $totalUsers;
        $this->info("   📊 移行対象: {$totalUsers}件のユーザーアイコン");
        
        $progressBar = $this->createProgressBar($totalUsers, 'users.icon');
        $progressBar->start();
        
        // バッチ処理でユーザーを処理
        User::whereNotNull('icon')
            ->where('icon', '!=', '')
            ->chunk($batchSize, function ($users) use ($isDryRun, $progressBar) {
                foreach ($users as $user) {
                    try {
                        if ($isDryRun) {
                            // Dry Run: 処理をシミュレート
                            $this->migrationStats['users']['migrated']++;
                        } else {
                            // 実際の移行実行
                            if ($this->migrateUserIcon($user)) {
                                $this->migrationStats['users']['migrated']++;
                            } else {
                                $this->migrationStats['users']['skipped']++;
                            }
                        }
                        
                        $progressBar->advance();
                        
                    } catch (Exception $e) {
                        $this->migrationStats['users']['errors']++;
                        $this->logError("User icon migration failed for user {$user->id}", $e);
                        $progressBar->advance();
                    }
                }
            });
        
        $progressBar->finish();
        $this->newLine(2);
        
        $migrated = $this->migrationStats['users']['migrated'];
        $errors = $this->migrationStats['users']['errors'];
        
        if ($isDryRun) {
            $this->info("   ✅ DRY RUN: {$migrated}件のユーザーアイコンが移行対象です");
        } else {
            $this->info("   ✅ {$migrated}件のユーザーアイコンを移行完了");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors}件でエラーが発生しました");
            }
        }
    }

    /**
     * 個別ユーザーアイコンの移行処理
     */
    private function migrateUserIcon(User $user): bool
    {
        try {
            // 既存のAttachmentをチェック（重複回避）
            if ($user->attachments()->where('file_type', 'image')->exists()) {
                return false; // すでに移行済み
            }
            
            // アイコンファイルの存在確認
            $iconPath = $user->icon;
            
            if (!$iconPath || !Storage::disk('public')->exists($iconPath)) {
                return false; // ファイルが存在しない
            }
            
            // ファイル情報取得
            $fullPath = storage_path('app/public/' . $iconPath);
            $originalName = basename($iconPath);
            $fileSize = filesize($fullPath);
            $mimeType = mime_content_type($fullPath);
            
            // ファイル拡張子とタイプ判定
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                return false; // サポートされていない画像形式
            }
            
            // 安全なファイル名生成
            $safeFileName = $this->generateSafeFileName($originalName, $extension);
            $newPath = 'attachments/images/' . $safeFileName;
            
            // ファイルハッシュ生成
            $fileHash = hash_file('sha256', $fullPath);
            
            // トランザクション内で処理
            DB::transaction(function () use ($user, $originalName, $safeFileName, $newPath, $fileSize, $mimeType, $fileHash, $iconPath) {
                // 新しい場所にファイルコピー
                Storage::disk('public')->copy($iconPath, $newPath);
                
                // Attachmentレコード作成
                Attachment::create([
                    'attachable_type' => 'App\Models\User',
                    'attachable_id' => $user->id,
                    'original_name' => $originalName,
                    'file_name' => $safeFileName,
                    'file_path' => $newPath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'file_type' => 'image',
                    'tenant_id' => $user->tenant_id,
                    'uploaded_by' => $user->id,
                    'hash' => $fileHash,
                    'is_safe' => true
                ]);
            });
            
            $this->logInfo("User icon migrated successfully", [
                'user_id' => $user->id,
                'original_path' => $iconPath,
                'new_path' => $newPath
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError("Failed to migrate user icon for user {$user->id}", $e);
            throw $e;
        }
    }

    /**
     * 安全なファイル名生成
     */
    private function generateSafeFileName(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($name) ?: 'file';
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        
        return "{$safeName}_{$timestamp}_{$random}.{$extension}";
    }
}
