<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exceptions\Custom\TenantViolationException;

class TenantViolationExceptionTest extends TestCase
{
    /**
     * 基本的な例外生成のテスト
     */
    public function test_basic_exception_creation(): void
    {
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $this->assertEquals('tenant-1', $exception->currentTenantId);
        $this->assertEquals('tenant-2', $exception->resourceTenantId);
        $this->assertEquals('post', $exception->resourceType);
        $this->assertEquals(123, $exception->resourceId);
        $this->assertEquals(403, $exception->getCode());
    }

    /**
     * カスタムメッセージのテスト
     */
    public function test_custom_message(): void
    {
        $customMessage = 'Custom violation message';
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123,
            message: $customMessage
        );

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /**
     * デフォルトメッセージ生成のテスト
     */
    public function test_default_message_generation(): void
    {
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $expectedMessage = 'テナント境界違反: ユーザーのテナント[tenant-1]がpost[ID:123]のテナント[tenant-2]と一致しません';
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * ログコンテキストのテスト
     */
    public function test_log_context(): void
    {
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $context = $exception->getLogContext();

        $this->assertArrayHasKey('exception_type', $context);
        $this->assertArrayHasKey('current_tenant_id', $context);
        $this->assertArrayHasKey('resource_tenant_id', $context);
        $this->assertArrayHasKey('resource_type', $context);
        $this->assertArrayHasKey('resource_id', $context);
        $this->assertArrayHasKey('security_incident', $context);

        $this->assertEquals('tenant_violation', $context['exception_type']);
        $this->assertEquals('tenant-1', $context['current_tenant_id']);
        $this->assertEquals('tenant-2', $context['resource_tenant_id']);
        $this->assertEquals('post', $context['resource_type']);
        $this->assertEquals(123, $context['resource_id']);
        $this->assertTrue($context['security_incident']);
    }

    /**
     * ユーザーメッセージのテスト
     */
    public function test_user_message(): void
    {
        $exception = new TenantViolationException(
            currentTenantId: 'tenant-1',
            resourceTenantId: 'tenant-2',
            resourceType: 'post',
            resourceId: 123
        );

        $userMessage = $exception->getUserMessage();
        $this->assertEquals('アクセス権限がありません。管理者にお問い合わせください。', $userMessage);
    }

    /**
     * 異なるリソースタイプでのテスト
     */
    public function test_different_resource_types(): void
    {
        $resourceTypes = ['post', 'comment', 'forum', 'user'];

        foreach ($resourceTypes as $type) {
            $exception = new TenantViolationException(
                currentTenantId: 'tenant-1',
                resourceTenantId: 'tenant-2',
                resourceType: $type,
                resourceId: 123
            );

            $this->assertEquals($type, $exception->resourceType);
            $this->assertStringContainsString($type, $exception->getMessage());
        }
    }
}