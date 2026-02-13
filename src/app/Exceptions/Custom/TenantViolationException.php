<?php

namespace App\Exceptions\Custom;

use Exception;

/**
 * テナント境界違反例外
 * 
 * マルチテナント環境でのデータアクセス時に、
 * 異なるテナント間でのリソースアクセス違反を検出した場合に発生
 */
class TenantViolationException extends Exception
{
    /**
     * 現在のユーザーのテナントID
     */
    public readonly string $currentTenantId;

    /**
     * アクセス対象のリソースのテナントID
     */
    public readonly string $resourceTenantId;

    /**
     * リソースの種類（post, comment, etc.)
     */
    public readonly string $resourceType;

    /**
     * リソースのID
     */
    public readonly int $resourceId;

    public function __construct(
        string $currentTenantId,
        string $resourceTenantId,
        string $resourceType,
        int $resourceId,
        string $message = null,
        int $code = 403,
        Exception $previous = null
    ) {
        $this->currentTenantId = $currentTenantId;
        $this->resourceTenantId = $resourceTenantId;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;

        $message = $message ?: sprintf(
            'テナント境界違反: ユーザーのテナント[%s]が%s[ID:%d]のテナント[%s]と一致しません',
            $currentTenantId,
            $resourceType,
            $resourceId,
            $resourceTenantId
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * ログ出力用の詳細情報を取得
     */
    public function getLogContext(): array
    {
        return [
            'exception_type' => 'tenant_violation',
            'current_tenant_id' => $this->currentTenantId,
            'resource_tenant_id' => $this->resourceTenantId,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'security_incident' => true,
        ];
    }

    /**
     * ユーザー向けの安全なエラーメッセージを取得
     */
    public function getUserMessage(): string
    {
        return 'アクセス権限がありません。管理者にお問い合わせください。';
    }
}