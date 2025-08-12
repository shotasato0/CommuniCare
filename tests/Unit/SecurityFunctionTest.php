<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PostService;
use App\Services\ForumService;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\PostOwnershipException;
use Illuminate\Support\Facades\Auth;
use Mockery;

/**
 * セキュリティ機能専用テスト
 * 
 * マルチテナント環境でのセキュリティクリティカルな機能を徹底テスト：
 * - テナント境界違反の検出と防止
 * - 投稿所有権の厳密な検証
 * - 管理者権限の適切なチェック
 * - セキュリティ例外の詳細ログ出力
 */
class SecurityFunctionTest extends TestCase
{
    private PostService $postService;
    private ForumService $forumService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = new PostService();
        $this->forumService = new ForumService();
    }

    /**
     * テナント境界違反例外の詳細コンテキスト検証
     */
    public function test_tenant_violation_exception_detailed_context()
    {
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        // 例外の基本情報
        $this->assertEquals('tenant-1', $exception->currentTenantId);
        $this->assertEquals('tenant-2', $exception->resourceTenantId);
        $this->assertEquals('post', $exception->resourceType);
        $this->assertEquals(123, $exception->resourceId);
        $this->assertEquals(403, $exception->getCode());

        // セキュリティログ用コンテキスト
        $logContext = $exception->getLogContext();
        $this->assertEquals('tenant_violation', $logContext['exception_type']);
        $this->assertEquals('tenant-1', $logContext['current_tenant_id']);
        $this->assertEquals('tenant-2', $logContext['resource_tenant_id']);
        $this->assertEquals('post', $logContext['resource_type']);
        $this->assertEquals(123, $logContext['resource_id']);
        $this->assertTrue($logContext['security_incident']);

        // ユーザー向け安全なメッセージ
        $this->assertEquals('アクセス権限がありません。管理者にお問い合わせください。', $exception->getUserMessage());
    }

    /**
     * 投稿所有権違反例外の詳細コンテキスト検証
     */
    public function test_post_ownership_exception_detailed_context()
    {
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false
        );

        // 例外の基本情報
        $this->assertEquals(1, $exception->userId);
        $this->assertEquals(123, $exception->postId);
        $this->assertEquals(2, $exception->postOwnerId);
        $this->assertEquals('delete', $exception->operation);
        $this->assertFalse($exception->isAdmin);
        $this->assertEquals(403, $exception->getCode());

        // セキュリティログ用コンテキスト
        $logContext = $exception->getLogContext();
        $this->assertEquals('post_ownership_violation', $logContext['exception_type']);
        $this->assertEquals(1, $logContext['user_id']);
        $this->assertEquals(123, $logContext['post_id']);
        $this->assertEquals(2, $logContext['post_owner_id']);
        $this->assertEquals('delete', $logContext['operation']);
        $this->assertFalse($logContext['is_admin']);
        $this->assertTrue($logContext['authorization_failed']);

        // ユーザー向け安全なメッセージ
        $this->assertEquals('この投稿を削除する権限がありません。', $exception->getUserMessage());
    }

    /**
     * 管理者権限による例外処理の違い検証
     */
    public function test_admin_privilege_exception_handling()
    {
        // 管理者の場合
        $adminException = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: true
        );

        $this->assertTrue($adminException->isAdmin);
        $logContext = $adminException->getLogContext();
        $this->assertTrue($logContext['is_admin']);

        // 権限不足理由の分析
        $reason = $adminException->getAuthorizationFailureReason();
        $this->assertEquals('ユーザーは管理者ですが、操作が許可されていません', $reason);

        // 非管理者の場合
        $userException = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false
        );

        $reason = $userException->getAuthorizationFailureReason();
        $this->assertEquals('ユーザーは投稿の所有者でも管理者でもありません', $reason);
    }

    /**
     * 異なる操作タイプでの例外メッセージ検証
     */
    public function test_different_operation_exception_messages()
    {
        $operations = [
            'delete' => 'この投稿を削除する権限がありません。',
            'update' => 'この投稿を編集する権限がありません。',
            'edit' => 'この投稿を編集する権限がありません。',
            'view' => 'この投稿を閲覧する権限がありません。',
            'custom' => 'この操作を実行する権限がありません。'
        ];

        foreach ($operations as $operation => $expectedMessage) {
            $exception = new PostOwnershipException(
                userId: 1,
                postId: 123,
                postOwnerId: 2,
                operation: $operation,
                isAdmin: false
            );

            $this->assertEquals($expectedMessage, $exception->getUserMessage());
        }
    }

    /**
     * PostServiceのセキュリティメソッド検証
     */
    public function test_post_service_security_methods()
    {
        $reflection = new \ReflectionClass($this->postService);

        // validatePostOwnershipメソッドの存在とアクセシビリティ
        $this->assertTrue($reflection->hasMethod('validatePostOwnership'));
        $validateMethod = $reflection->getMethod('validatePostOwnership');
        $this->assertTrue($validateMethod->isPrivate()); // セキュリティメソッドはprivate

        // canDeletePostメソッドの存在（公開メソッド）
        $this->assertTrue($reflection->hasMethod('canDeletePost'));
        $canDeleteMethod = $reflection->getMethod('canDeletePost');
        $this->assertTrue($canDeleteMethod->isPublic());
    }

    /**
     * セキュリティ例外のメッセージ生成パターン検証
     */
    public function test_security_exception_message_patterns()
    {
        // TenantViolationExceptionのメッセージパターン
        $tenantException = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $expectedTenantMessage = 'テナント境界違反: ユーザーのテナント[tenant-1]がpost[ID:123]のテナント[tenant-2]と一致しません';
        $this->assertEquals($expectedTenantMessage, $tenantException->getMessage());

        // PostOwnershipExceptionのメッセージパターン
        $ownershipException = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false
        );

        $expectedOwnershipMessage = '投稿所有権エラー: ユーザー[ID:1]は投稿[ID:123]（所有者:2）を削除できません';
        $this->assertEquals($expectedOwnershipMessage, $ownershipException->getMessage());
    }

    /**
     * セキュリティインシデントフラグの検証
     */
    public function test_security_incident_flags()
    {
        $tenantException = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $logContext = $tenantException->getLogContext();
        $this->assertTrue($logContext['security_incident']); // セキュリティインシデントフラグ

        $ownershipException = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false
        );

        $ownershipLogContext = $ownershipException->getLogContext();
        $this->assertTrue($ownershipLogContext['authorization_failed']); // 認証失敗フラグ
    }

    /**
     * カスタムメッセージオーバーライドの検証
     */
    public function test_custom_message_override()
    {
        $customMessage = 'Custom security violation message';

        $tenantException = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123,
            message: $customMessage
        );

        $this->assertEquals($customMessage, $tenantException->getMessage());

        $ownershipException = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false,
            message: $customMessage
        );

        $this->assertEquals($customMessage, $ownershipException->getMessage());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}