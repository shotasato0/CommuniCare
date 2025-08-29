<?php

namespace App\Traits;

use App\Models\User;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

trait TenantBoundaryCheckTrait
{
    /**
     * テナント境界チェック（汎用）
     */
    protected function validateTenantBoundary(Model $resource, ?int $expectedTenantId = null): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $tenantId = $expectedTenantId ?? $currentUser->tenant_id;
        
        if (!isset($resource->tenant_id) || $resource->tenant_id !== $tenantId) {
            $this->logSecurityViolation(
                "テナント境界違反",
                $resource,
                $tenantId,
                $resource->tenant_id ?? null
            );
            
            throw new TenantViolationException(
                currentTenantId: (string) $currentUser->tenant_id,
                resourceTenantId: (string) ($resource->tenant_id ?? ''),
                resourceType: get_class($resource),
                resourceId: (int) ($resource->id ?? 0),
                message: "他のテナントのリソースにアクセスしようとしました。"
            );
        }
    }

    /**
     * リソース存在チェック（テナント境界込み）
     */
    protected function findResourceWithTenantCheck(string $modelClass, int $resourceId): Model
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $resource = $modelClass::find($resourceId);
        
        if (!$resource) {
            throw new TenantViolationException(
                currentTenantId: (string) $currentUser->tenant_id,
                resourceTenantId: '',
                resourceType: $modelClass,
                resourceId: (int) $resourceId,
                message: "指定されたリソースが見つかりません。"
            );
        }
        
        $this->validateTenantBoundary($resource);
        
        return $resource;
    }

    /**
     * テナント専用クエリスコープ適用
     */
    protected function scopeToCurrentTenant($query)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return $query->where('tenant_id', $currentUser->tenant_id);
    }

    /**
     * 複数リソースのテナント境界一括チェック
     */
    protected function validateMultipleTenantBoundaries(array $resources): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        foreach ($resources as $resource) {
            if (!$resource instanceof Model) {
                continue;
            }
            
            $this->validateTenantBoundary($resource, $currentUser->tenant_id);
        }
    }

    /**
     * テナント境界違反の詳細ログ記録
     */
    private function logSecurityViolation(
        string $violationType,
        Model $resource,
        int $expectedTenantId,
        ?int $actualTenantId
    ): void {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $logContext = [
            'violation_type' => $violationType,
            'user_id' => $currentUser->id,
            'user_tenant_id' => $currentUser->tenant_id,
            'expected_tenant_id' => $expectedTenantId,
            'actual_tenant_id' => $actualTenantId,
            'resource_type' => get_class($resource),
            'resource_id' => $resource->id ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];
        
        if (!app()->environment('production')) {
            $logContext['stack_trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }
        
        Log::critical("テナントセキュリティ違反検出", $logContext);
    }

    /**
     * 関連テーブルのテナント境界チェック
     * 例: Post -> Forum -> Unit の連鎖チェック
     */
    protected function validateRelatedResourceTenant(Model $resource, string $relationPath): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $relations = explode('.', $relationPath);
        $currentResource = $resource;
        
        foreach ($relations as $relation) {
            if (!$currentResource->{$relation}) {
                throw new TenantViolationException(
                    "関連リソースが見つかりません。",
                    [
                        'user_id' => $currentUser->id,
                        'tenant_id' => $currentUser->tenant_id,
                        'resource_type' => get_class($resource),
                        'resource_id' => $resource->id ?? null,
                        'missing_relation' => $relation,
                        'action' => 'related_resource_check'
                    ]
                );
            }
            
            $currentResource = $currentResource->{$relation};
            $this->validateTenantBoundary($currentResource);
        }
    }

    /**
     * バッチ操作時のテナント境界チェック
     */
    protected function validateBatchTenantBoundary(array $resourceIds, string $modelClass): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $resources = $modelClass::whereIn('id', $resourceIds)
            ->where('tenant_id', $currentUser->tenant_id)
            ->get();
            
        if ($resources->count() !== count($resourceIds)) {
            $foundIds = $resources->pluck('id')->toArray();
            $missingIds = array_diff($resourceIds, $foundIds);
            
            throw new TenantViolationException(
                "一部のリソースへのアクセスが許可されていません。",
                [
                    'user_id' => $currentUser->id,
                    'tenant_id' => $currentUser->tenant_id,
                    'resource_type' => $modelClass,
                    'requested_ids' => $resourceIds,
                    'missing_ids' => $missingIds,
                    'action' => 'batch_tenant_boundary_check'
                ]
            );
        }
        
        return $resources->toArray();
    }
}
