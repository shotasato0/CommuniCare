<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Exception;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // 🚨 第1段階：環境チェック（絶対に破ってはいけない壁）
        $this->validateTestingEnvironment();
        
        parent::setUp();
        
        // 🚨 第2段階：データベース接続チェック
        $this->validateDatabaseConfiguration();
        
        // 🚨 第3段階：危険なDB名の検出
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
     */
    public function refreshDatabase(): void
    {
        throw new Exception('🚨 セキュリティ違反: RefreshDatabase の使用は禁止されています。代わりに安全なデータ生成メソッドを使用してください。');
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
}
