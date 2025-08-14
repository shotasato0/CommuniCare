<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Resident\ResidentStoreRequest;
use App\Http\Requests\Resident\ResidentUpdateRequest;
use App\Exceptions\Custom\TenantViolationException;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;
use Illuminate\Support\Collection;

class ResidentService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    /**
     * テナントに属する利用者一覧を取得
     */
    public function getResidents(?int $unitId = null): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $query = Resident::where('tenant_id', $currentUser->tenant_id);
        
        // unit_idが指定されている場合の最適化されたチェック
        if ($unitId) {
            // N+1問題回避: unit存在チェックを効率化
            $unitExists = Unit::where('id', $unitId)
                ->where('tenant_id', $currentUser->tenant_id)
                ->exists();
                
            if (!$unitExists) {
                throw new TenantViolationException(
                    "指定された部署へのアクセスが許可されていません。",
                    [
                        'user_id' => $currentUser->id,
                        'tenant_id' => $currentUser->tenant_id,
                        'requested_unit_id' => $unitId,
                        'action' => 'resident_filter_by_unit'
                    ]
                );
            }
            
            $query->where('unit_id', $unitId);
        }
        
        return $query
            ->with(['unit' => function($query) use ($currentUser) {
                $query->select('id', 'name', 'sort_order', 'tenant_id')
                      ->where('tenant_id', $currentUser->tenant_id);
            }])
            ->select('id', 'name', 'unit_id', 'tenant_id', 'meal_support', 'toilet_support', 'bathing_support', 'mobility_support', 'memo', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * テナントに属する部署一覧を取得
     */
    public function getUnitsForTenant(): Collection
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return Unit::where('tenant_id', $currentUser->tenant_id)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 利用者を新規作成
     */
    public function createResident(ResidentStoreRequest $request): Resident
    {
        $validated = $request->validated();
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // unit_idが指定されている場合、テナント境界チェック
        if (isset($validated['unit_id'])) {
            $this->validateUnitAccess($validated['unit_id'], $currentUser);
        }

        return Resident::create(array_merge($validated, [
            'tenant_id' => $currentUser->tenant_id
        ]));
    }

    /**
     * 利用者情報を取得（テナント境界チェック付き）
     */
    public function getResident(int $residentId): Resident
    {
        $resident = $this->findResourceWithTenantCheck(Resident::class, $residentId);
        
        return $resident->load('unit');
    }

    /**
     * 利用者情報を更新
     */
    public function updateResident(ResidentUpdateRequest $request, int $residentId): Resident
    {
        $validated = $request->validated();
        $resident = $this->getResident($residentId); // テナント境界チェック済み

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // unit_idが変更される場合、新しいunitのテナント境界チェック
        if (isset($validated['unit_id']) && $validated['unit_id'] !== $resident->unit_id) {
            $this->validateUnitAccess($validated['unit_id'], $currentUser);
        }

        $resident->update($validated);
        
        return $resident->fresh('unit');
    }

    /**
     * 利用者を削除
     */
    public function deleteResident(int $residentId): void
    {
        $resident = $this->getResident($residentId); // テナント境界チェック済み
        
        DB::transaction(function () use ($resident) {
            // 利用者削除時の追加処理があればここに記述
            // 例：関連するケア記録の削除など
            
            $resident->delete();
        });
    }

    /**
     * 部署のテナント境界チェック
     */
    private function validateUnitAccess(int $unitId, User $currentUser): void
    {
        $this->findResourceWithTenantCheck(Unit::class, $unitId);
    }

    /**
     * 管理者権限チェック
     */
    public function isAdmin(): bool
    {
        return $this->isCurrentUserAdmin();
    }
}