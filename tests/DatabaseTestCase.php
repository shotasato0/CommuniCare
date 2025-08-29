<?php

namespace Tests;

/**
 * データベースを使用するテスト用の基底クラス
 * CommuniCareV2セキュリティ要件に準拠しつつ、必要なデータベース操作を提供
 */
abstract class DatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 安全なマイグレーション実行
        $this->runSafeMigrations();
    }
    
    /**
     * テスト用テナント作成（安全なテストデータ生成）
     */
    protected function createTestTenant(string $id = 'test-tenant'): \App\Models\Tenant
    {
        return \App\Models\Tenant::create([
            'id' => $id . '-' . uniqid(),
            'data' => [],
        ]);
    }
    
    /**
     * テスト用ユーザー作成（テナント境界を考慮）
     */
    protected function createTestUser(\App\Models\Tenant $tenant): \App\Models\User
    {
        return \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);
    }
    
    /**
     * テスト用投稿作成（Attachment関連テスト用）
     */
    protected function createTestPost(\App\Models\User $user, int $forumId = 1): \App\Models\Post
    {
        return \App\Models\Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'message' => 'Test message',
            'forum_id' => $forumId,
            'tenant_id' => $user->tenant_id,
        ]);
    }
}