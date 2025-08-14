<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\UnitService;
use App\Models\Unit;
use App\Models\Forum;
use App\Models\User;
use App\Http\Requests\Unit\UnitStoreRequest;
use App\Http\Requests\Unit\UnitSortRequest;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class UnitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $unitService;
    protected $tenant1User;
    protected $tenant2User;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->unitService = new UnitService();
        
        $this->tenant1User = User::factory()->create(['tenant_id' => 1]);
        $this->tenant2User = User::factory()->create(['tenant_id' => 2]);
    }

    public function test_getUnitsWithForum_returns_only_tenant_units()
    {
        Auth::login($this->tenant1User);
        
        // テナント1の部署作成
        $tenant1Unit = Unit::factory()->create(['tenant_id' => 1]);
        Forum::factory()->create(['unit_id' => $tenant1Unit->id, 'tenant_id' => 1]);
        
        // テナント2の部署作成（アクセス不可）
        $tenant2Unit = Unit::factory()->create(['tenant_id' => 2]);
        Forum::factory()->create(['unit_id' => $tenant2Unit->id, 'tenant_id' => 2]);
        
        $units = $this->unitService->getUnitsWithForum();
        
        $this->assertCount(1, $units);
        $this->assertEquals($tenant1Unit->id, $units->first()->id);
        $this->assertEquals(1, $units->first()->tenant_id);
    }

    public function test_createUnit_creates_unit_and_forum_for_current_tenant()
    {
        Auth::login($this->tenant1User);
        
        $request = new UnitStoreRequest();
        $request->merge([
            'name' => 'テスト部署',
            'description' => 'テスト説明',
            'visibility' => 'public'
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $unit = $this->unitService->createUnit($request);
        
        $this->assertEquals('テスト部署', $unit->name);
        $this->assertEquals(1, $unit->tenant_id);
        
        // フォーラムも作成されているかチェック
        $forum = Forum::where('unit_id', $unit->id)->first();
        $this->assertNotNull($forum);
        $this->assertEquals('テスト部署', $forum->name);
        $this->assertEquals(1, $forum->tenant_id);
    }

    public function test_deleteUnit_throws_exception_for_different_tenant_unit()
    {
        Auth::login($this->tenant1User);
        
        $tenant2Unit = Unit::factory()->create(['tenant_id' => 2]);
        
        $this->expectException(TenantViolationException::class);
        $this->expectExceptionMessage("指定されたリソースが見つかりません。");
        
        $this->unitService->deleteUnit($tenant2Unit->id);
    }

    public function test_updateSortOrder_validates_all_units_belong_to_tenant()
    {
        Auth::login($this->tenant1User);
        
        $tenant1Unit = Unit::factory()->create(['tenant_id' => 1, 'sort_order' => 0]);
        $tenant2Unit = Unit::factory()->create(['tenant_id' => 2, 'sort_order' => 1]);
        
        $request = new UnitSortRequest();
        $request->merge([
            'units' => [
                ['id' => $tenant1Unit->id],
                ['id' => $tenant2Unit->id], // 異なるテナントの部署
            ]
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $this->expectException(TenantViolationException::class);
        $this->expectExceptionMessage("並び順更新対象の部署にアクセスする権限がありません。");
        
        $this->unitService->updateSortOrder($request);
    }

    public function test_getUnit_throws_exception_for_different_tenant()
    {
        Auth::login($this->tenant1User);
        
        $tenant2Unit = Unit::factory()->create(['tenant_id' => 2]);
        
        $this->expectException(TenantViolationException::class);
        
        $this->unitService->getUnit($tenant2Unit->id);
    }

    public function test_successful_unit_operations_within_same_tenant()
    {
        Auth::login($this->tenant1User);
        
        // 正常な作成テスト
        $createRequest = new UnitStoreRequest();
        $createRequest->merge([
            'name' => 'テスト部署',
            'description' => 'テスト説明'
        ]);
        $createRequest->setValidator(validator($createRequest->all(), $createRequest->rules()));
        
        $unit = $this->unitService->createUnit($createRequest);
        $this->assertEquals('テスト部署', $unit->name);
        
        // 正常な取得テスト
        $retrievedUnit = $this->unitService->getUnit($unit->id);
        $this->assertEquals($unit->id, $retrievedUnit->id);
        
        // 正常な並び順更新テスト
        $sortRequest = new UnitSortRequest();
        $sortRequest->merge([
            'units' => [
                ['id' => $unit->id]
            ]
        ]);
        $sortRequest->setValidator(validator($sortRequest->all(), $sortRequest->rules()));
        
        $this->unitService->updateSortOrder($sortRequest);
        
        $updatedUnit = Unit::find($unit->id);
        $this->assertEquals(0, $updatedUnit->sort_order);
        
        // 正常な削除テスト
        $this->unitService->deleteUnit($unit->id);
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_sort_order_increments_correctly()
    {
        Auth::login($this->tenant1User);
        
        // 既存の部署を作成
        Unit::factory()->create(['tenant_id' => 1, 'sort_order' => 5]);
        
        $request = new UnitStoreRequest();
        $request->merge([
            'name' => '新規部署',
            'description' => '説明'
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $unit = $this->unitService->createUnit($request);
        
        $this->assertEquals(6, $unit->sort_order); // max(5) + 1
    }
}