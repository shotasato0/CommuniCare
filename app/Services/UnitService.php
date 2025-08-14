<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Unit\UnitStoreRequest;
use App\Http\Requests\Unit\UnitSortRequest;
use App\Exceptions\Custom\TenantViolationException;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;
use Illuminate\Support\Collection;

class UnitService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    /**
     * テナントに属する部署一覧を取得（フォーラム情報付き）
     */
    public function getUnitsWithForum(): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return Unit::where('tenant_id', $currentUser->tenant_id)
            ->with('forum')
            ->get();
    }

    /**
     * 部署作成画面用の部署一覧を取得
     */
    public function getUnitsForManagement(): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return Unit::select('id', 'name', 'sort_order', 'created_at')
            ->where('tenant_id', $currentUser->tenant_id)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * テナントに属するフォーラム一覧を取得
     */
    public function getForumsForTenant(): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return Forum::where('tenant_id', $currentUser->tenant_id)->get();
    }

    /**
     * 新しい部署を作成（フォーラムも同時作成）
     */
    public function createUnit(UnitStoreRequest $request): Unit
    {
        $validated = $request->validated();
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return DB::transaction(function () use ($validated, $currentUser) {
            // 最新の並び順を取得
            $maxSortOrder = Unit::where('tenant_id', $currentUser->tenant_id)
                ->max('sort_order') ?? -1;

            $unit = Unit::create([
                'name' => $validated['name'],
                'tenant_id' => $currentUser->tenant_id,
                'sort_order' => $maxSortOrder + 1,
            ]);

            // 部署に対応するフォーラムを作成
            Forum::create([
                'name' => $validated['name'],
                'unit_id' => $unit->id,
                'description' => $validated['description'] ?? '',
                'visibility' => $validated['visibility'] ?? 'public',
                'status' => 'active',
                'tenant_id' => $currentUser->tenant_id,
            ]);

            return $unit;
        });
    }

    /**
     * 部署を削除（テナント境界チェック付き）
     */
    public function deleteUnit(int $unitId): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $unit = Unit::where('id', $unitId)
            ->where('tenant_id', $currentUser->tenant_id)
            ->first();
            
        if (!$unit) {
            throw new TenantViolationException(
                "指定された部署へのアクセスが許可されていません。",
                [
                    'user_id' => $currentUser->id,
                    'tenant_id' => $currentUser->tenant_id,
                    'requested_unit_id' => $unitId,
                    'action' => 'unit_delete'
                ]
            );
        }

        DB::transaction(function () use ($unit) {
            // 関連するフォーラムも削除
            $unit->forum()?->delete();
            
            // 部署を削除
            $unit->delete();
        });
    }

    /**
     * 部署の並び順を更新
     */
    public function updateSortOrder(UnitSortRequest $request): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $units = $request->validated()['units'];
        
        DB::transaction(function () use ($units, $currentUser) {
            foreach ($units as $index => $unitData) {
                // セキュリティチェック: 更新対象の部署がテナントに属するかチェック
                $unit = Unit::where('id', $unitData['id'])
                    ->where('tenant_id', $currentUser->tenant_id)
                    ->first();
                    
                if (!$unit) {
                    throw new TenantViolationException(
                        "並び順更新対象の部署にアクセスする権限がありません。",
                        [
                            'user_id' => $currentUser->id,
                            'tenant_id' => $currentUser->tenant_id,
                            'requested_unit_id' => $unitData['id'],
                            'action' => 'unit_sort_update'
                        ]
                    );
                }
                
                $unit->update(['sort_order' => $index]);
            }
        });
    }

    /**
     * 部署の詳細を取得（テナント境界チェック付き）
     */
    public function getUnit(int $unitId): Unit
    {
        return $this->findResourceWithTenantCheck(Unit::class, $unitId);
    }
}