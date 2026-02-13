<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Exceptions\Custom\PostOwnershipException;

class PostOwnershipExceptionTest extends TestCase
{
    /**
     * 基本的な例外生成のテスト
     */
    public function test_basic_exception_creation(): void
    {
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete'
        );

        $this->assertEquals(1, $exception->userId);
        $this->assertEquals(123, $exception->postId);
        $this->assertEquals(2, $exception->postOwnerId);
        $this->assertEquals('delete', $exception->operation);
        $this->assertFalse($exception->isAdmin);
        $this->assertEquals(403, $exception->getCode());
    }

    /**
     * 管理者権限ありの例外生成テスト
     */
    public function test_exception_with_admin_privileges(): void
    {
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: true
        );

        $this->assertTrue($exception->isAdmin);
        $this->assertStringContainsString('(管理者)', $exception->getMessage());
    }

    /**
     * カスタムメッセージのテスト
     */
    public function test_custom_message(): void
    {
        $customMessage = 'Custom ownership violation message';
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            message: $customMessage
        );

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /**
     * デフォルトメッセージ生成のテスト
     */
    public function test_default_message_generation(): void
    {
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete'
        );

        $expectedMessage = '投稿所有権エラー: ユーザー[ID:1]は投稿[ID:123]（所有者:2）を削除できません';
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    /**
     * 操作テキストの変換テスト
     */
    public function test_operation_text_conversion(): void
    {
        $operations = [
            'delete' => '削除',
            'update' => '更新',
            'edit' => '編集',
            'view' => '閲覧'
        ];

        foreach ($operations as $operation => $expectedText) {
            $exception = new PostOwnershipException(
                userId: 1,
                postId: 123,
                postOwnerId: 2,
                operation: $operation
            );

            $this->assertStringContainsString($expectedText, $exception->getMessage());
        }
    }

    /**
     * ログコンテキストのテスト
     */
    public function test_log_context(): void
    {
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: true
        );

        $context = $exception->getLogContext();

        $this->assertArrayHasKey('exception_type', $context);
        $this->assertArrayHasKey('user_id', $context);
        $this->assertArrayHasKey('post_id', $context);
        $this->assertArrayHasKey('post_owner_id', $context);
        $this->assertArrayHasKey('operation', $context);
        $this->assertArrayHasKey('is_admin', $context);
        $this->assertArrayHasKey('authorization_failed', $context);

        $this->assertEquals('post_ownership_violation', $context['exception_type']);
        $this->assertEquals(1, $context['user_id']);
        $this->assertEquals(123, $context['post_id']);
        $this->assertEquals(2, $context['post_owner_id']);
        $this->assertEquals('delete', $context['operation']);
        $this->assertTrue($context['is_admin']);
        $this->assertTrue($context['authorization_failed']);
    }

    /**
     * ユーザーメッセージのテスト（異なる操作）
     */
    public function test_user_messages_for_different_operations(): void
    {
        $operations = [
            'delete' => 'この投稿を削除する権限がありません。',
            'update' => 'この投稿を編集する権限がありません。',
            'edit' => 'この投稿を編集する権限がありません。',
            'view' => 'この投稿を閲覧する権限がありません。',
            'unknown' => 'この操作を実行する権限がありません。'
        ];

        foreach ($operations as $operation => $expectedMessage) {
            $exception = new PostOwnershipException(
                userId: 1,
                postId: 123,
                postOwnerId: 2,
                operation: $operation
            );

            $this->assertEquals($expectedMessage, $exception->getUserMessage());
        }
    }

    /**
     * 権限不足理由の取得テスト
     */
    public function test_authorization_failure_reasons(): void
    {
        // 所有者だが他の条件で拒否された場合
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 1, // 同じユーザーID
            operation: 'delete'
        );

        $reason = $exception->getAuthorizationFailureReason();
        $this->assertEquals('ユーザーは所有者ですが、他の条件で権限が拒否されました', $reason);

        // 管理者だが許可されていない場合
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: true
        );

        $reason = $exception->getAuthorizationFailureReason();
        $this->assertEquals('ユーザーは管理者ですが、操作が許可されていません', $reason);

        // 所有者でも管理者でもない場合
        $exception = new PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false
        );

        $reason = $exception->getAuthorizationFailureReason();
        $this->assertEquals('ユーザーは投稿の所有者でも管理者でもありません', $reason);
    }
}