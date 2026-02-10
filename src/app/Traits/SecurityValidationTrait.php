<?php

namespace App\Traits;

use App\Models\User;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Support\Facades\Auth;
use App\Facades\Logs;

trait SecurityValidationTrait
{
    /**
     * 現在ログイン中のユーザーを取得（型安全）
     */
    protected function getCurrentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();
        return $user;
    }

    /**
     * 管理者権限チェック
     */
    protected function isCurrentUserAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user->hasRole('admin');
    }

    /**
     * セキュリティログを記録
     */
    protected function logSecurityEvent(string $message, array $context = []): void
    {
        $user = $this->getCurrentUser();
        
        $logContext = array_merge([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'user_email' => $user->email,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context);
        
        Logs::warning("セキュリティイベント: {$message}", $logContext);
    }

    /**
     * リソースの所有者チェック（汎用）
     */
    protected function validateResourceOwnership($resource, string $ownerField = 'user_id'): void
    {
        $currentUser = $this->getCurrentUser();
        
        if ($resource->{$ownerField} !== $currentUser->id) {
            $this->logSecurityEvent("リソース所有権違反", [
                'resource_type' => get_class($resource),
                'resource_id' => $resource->id ?? null,
                'resource_owner_id' => $resource->{$ownerField},
                'current_user_id' => $currentUser->id,
                'action' => 'resource_ownership_check'
            ]);
            
            throw new TenantViolationException(
                currentTenantId: (string) $currentUser->tenant_id,
                resourceTenantId: (string) $currentUser->tenant_id,
                resourceType: get_class($resource),
                resourceId: (int) ($resource->id ?? 0),
                message: "このリソースへのアクセス権限がありません。"
            );
        }
    }

    /**
     * 管理者権限必須チェック
     */
    protected function requireAdminRole(): void
    {
        if (!$this->isCurrentUserAdmin()) {
            $currentUser = $this->getCurrentUser();
            
            $this->logSecurityEvent("管理者権限必須操作への不正アクセス試行", [
                'user_roles' => $currentUser->getRoleNames()->toArray(),
                'action' => 'admin_role_check'
            ]);
            
            throw new TenantViolationException(
                currentTenantId: (string) $currentUser->tenant_id,
                resourceTenantId: (string) $currentUser->tenant_id,
                resourceType: 'admin_operation',
                resourceId: 0,
                message: "この操作には管理者権限が必要です。"
            );
        }
    }

    /**
     * セキュリティ監査ログ
     */
    protected function auditAction(string $action, array $details = []): void
    {
        $user = $this->getCurrentUser();
        
        Logs::info("ユーザーアクション監査", array_merge([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action' => $action,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
        ], $details));
    }
}
