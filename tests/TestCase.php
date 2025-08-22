<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Exception;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // 🚨 第1段階：環境チェック（絶対に破ってはいけない壁）
        // なぜ必要か: テストが本番や開発環境で誤って実行されると、介護施設の重要データが消失・破壊されるリスクがあります。
        // 防ぐリスク: 介護記録、利用者情報、職員データ等の機密情報の消失やシステム障害を防止します。
        $this->validateTestingEnvironment();
        
        // 🚨 危険なトレイト使用検出（Laravel 12対応）
        $this->detectDangerousTraits();
        
        parent::setUp();
        
        // 🚨 第2段階：データベース接続チェック
        // なぜ必要か: テストがSQLiteやメモリDB以外で実行されると、実際のDBに影響を与える危険があります。
        // 防ぐリスク: マルチテナント環境での他施設データへの誤アクセスや開発DBのデータ破壊を防止します。
        $this->validateDatabaseConfiguration();
        
        // 🚨 第3段階：危険なDB名の検出
        // なぜ必要か: DB名に本番・開発用の名称が含まれている場合、誤って重要なDBに接続するリスクがあります。
        // 防ぐリスク: 介護業界の機密性要件に基づく本番・開発DBのデータ消失や改ざんを防止します。
        $this->validateDatabaseSafety();
    }
    
    /**
     * 🔒 第1段階：テスト環境の強制確認
     * テストは testing 環境でのみ実行可能
     */
    private function validateTestingEnvironment(): void
    {
        // 環境変数の直接チェック
        if (env('APP_ENV') !== 'testing') {
            throw new Exception('🚨 セキュリティ違反: APP_ENV が testing に設定されていません。現在の値: ' . env('APP_ENV'));
        }
    }
    
    /**
     * 🔒 第2段階：データベース設定の強制確認
     * SQLite + メモリDBのみ許可
     */
    private function validateDatabaseConfiguration(): void
    {
        // 直接env値で確認
        $defaultConnection = env('DB_CONNECTION');
        
        // SQLite以外は危険
        if ($defaultConnection !== 'sqlite') {
            throw new Exception('🚨 セキュリティ違反: テストは SQLite でのみ実行可能です。現在の接続: ' . $defaultConnection);
        }
        
        // メモリDB以外は危険
        $database = env('DB_DATABASE');
        if ($database !== ':memory:') {
            throw new Exception('🚨 セキュリティ違反: テストは メモリDB でのみ実行可能です。現在のDB: ' . $database);
        }
    }
    
    /**
     * 🔒 第3段階：危険なDB名の検出
     * 本番・開発環境への誤接続を防止
     */
    private function validateDatabaseSafety(): void
    {
        // 危険なDB名のブラックリスト
        $dangerousDatabases = [
            'laravel',
            'communicare_production', 
            'communicare_staging',
            'communicare_development',
            'communicare_dev',
            'production',
            'main',
            'master'
        ];
        
        $dbName = env('DB_DATABASE');
        
        foreach ($dangerousDatabases as $dangerous) {
            if (stripos($dbName, $dangerous) !== false && $dbName !== ':memory:') {
                throw new Exception("🚨 セキュリティ違反: 危険なDB名を検出しました。DB名: {$dbName}");
            }
        }
    }
    
    /**
     * 🔒 危険な操作の無効化
     * RefreshDatabase等の危険なトレイト使用を検出
     * Laravel 12互換性対応: メソッド名を変更してトレイト競合を回避
     */
    protected function preventRefreshDatabase(): void
    {
        // RefreshDatabaseトレイト使用検出のための処理を別メソッドに移行
        // 直接のメソッドオーバーライドではなく、setUp()での事前チェックで対応
    }
    
    /**
     * 🔒 RefreshDatabaseトレイト使用検出
     */
    private function detectDangerousTraits(): void
    {
        $reflection = new \ReflectionClass($this);
        $traits = $reflection->getTraitNames();
        
        $dangerousTraits = [
            'Illuminate\Foundation\Testing\RefreshDatabase',
            'Illuminate\Foundation\Testing\RefreshDatabaseState',
        ];
        
        foreach ($dangerousTraits as $dangerous) {
            if (in_array($dangerous, $traits, true)) {
                throw new Exception("🚨 セキュリティ違反: 危険なトレイト '{$dangerous}' の使用は禁止されています。");
            }
        }
    }
    
    /**
     * 🔒 危険なマイグレーション操作の無効化
     */
    public function artisan($command, $parameters = []): \Illuminate\Testing\PendingCommand
    {
        // 危険なコマンドのブラックリスト
        $dangerousCommands = [
            'migrate:fresh',
            'migrate:reset',
            'db:wipe',
            'migrate:rollback',
        ];
        
        foreach ($dangerousCommands as $dangerous) {
            if (stripos($command, $dangerous) !== false) {
                throw new Exception("🚨 セキュリティ違反: 危険なコマンド '{$command}' の実行は禁止されています。");
            }
        }
        
        return parent::artisan($command, $parameters);
    }
    
    /**
     * 🔒 安全なテスト用マイグレーション実行
     * SQLiteメモリDB環境でのみマイグレーションを許可
     */
    protected function runSafeMigrations(): void
    {
        // 安全性チェック（再確認）
        if (env('DB_CONNECTION') !== 'sqlite' || env('DB_DATABASE') !== ':memory:') {
            throw new Exception('🚨 セキュリティ違反: マイグレーションはSQLiteメモリDBでのみ実行可能です。');
        }
        
        // テスト用マイグレーション実行
        $this->artisan('migrate', [
            '--database' => 'sqlite',
            '--path' => 'database/migrations',
        ]);
    }
}
