<?php

namespace App\Exceptions\Custom;

use Exception;

/**
 * 投稿所有権例外
 * 
 * 投稿に対する操作（編集、削除など）において、
 * ユーザーが必要な所有権や権限を持たない場合に発生
 */
class PostOwnershipException extends Exception
{
    /**
     * 操作を試行したユーザーID
     */
    public readonly int $userId;

    /**
     * 操作対象の投稿ID
     */
    public readonly int $postId;

    /**
     * 投稿の実際の所有者ID
     */
    public readonly int $postOwnerId;

    /**
     * 試行した操作の種類（delete, update, etc.）
     */
    public readonly string $operation;

    /**
     * ユーザーが管理者権限を持っているか
     */
    public readonly bool $isAdmin;

    public function __construct(
        int $userId,
        int $postId,
        int $postOwnerId,
        string $operation,
        bool $isAdmin = false,
        string $message = null,
        int $code = 403,
        Exception $previous = null
    ) {
        $this->userId = $userId;
        $this->postId = $postId;
        $this->postOwnerId = $postOwnerId;
        $this->operation = $operation;
        $this->isAdmin = $isAdmin;

        $message = $message ?: sprintf(
            '投稿所有権エラー: ユーザー[ID:%d]%sは投稿[ID:%d]（所有者:%d）を%sできません',
            $userId,
            $isAdmin ? '(管理者)' : '',
            $postId,
            $postOwnerId,
            $this->getOperationText($operation)
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * 操作の日本語表現を取得
     */
    private function getOperationText(string $operation): string
    {
        return match ($operation) {
            'delete' => '削除',
            'update' => '更新',
            'edit' => '編集',
            'view' => '閲覧',
            default => $operation,
        };
    }

    /**
     * ログ出力用の詳細情報を取得
     */
    public function getLogContext(): array
    {
        return [
            'exception_type' => 'post_ownership_violation',
            'user_id' => $this->userId,
            'post_id' => $this->postId,
            'post_owner_id' => $this->postOwnerId,
            'operation' => $this->operation,
            'is_admin' => $this->isAdmin,
            'authorization_failed' => true,
        ];
    }

    /**
     * ユーザー向けの安全なエラーメッセージを取得
     */
    public function getUserMessage(): string
    {
        return match ($this->operation) {
            'delete' => 'この投稿を削除する権限がありません。',
            'update', 'edit' => 'この投稿を編集する権限がありません。',
            'view' => 'この投稿を閲覧する権限がありません。',
            default => 'この操作を実行する権限がありません。',
        };
    }

    /**
     * 権限不足の理由を取得（デバッグ用）
     */
    public function getAuthorizationFailureReason(): string
    {
        if ($this->userId === $this->postOwnerId) {
            return 'ユーザーは所有者ですが、他の条件で権限が拒否されました';
        }

        if ($this->isAdmin) {
            return 'ユーザーは管理者ですが、操作が許可されていません';
        }

        return 'ユーザーは投稿の所有者でも管理者でもありません';
    }
}