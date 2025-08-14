<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ResidentService;
use App\Models\Resident;
use App\Models\Unit;
use App\Models\User;
use App\Http\Requests\Resident\ResidentStoreRequest;
use App\Http\Requests\Resident\ResidentUpdateRequest;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class ResidentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $residentService;
    protected $tenant1User;
    protected $tenant2User;
    protected $tenant1Unit;
    protected $tenant2Unit;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->residentService = new ResidentService();
        
        // テナント1のユーザーとデータ
        $this->tenant1User = User::factory()->create(['tenant_id' => 1]);
        $this->tenant1Unit = Unit::factory()->create(['tenant_id' => 1]);
        
        // テナント2のユーザーとデータ
        $this->tenant2User = User::factory()->create(['tenant_id' => 2]);
        $this->tenant2Unit = Unit::factory()->create(['tenant_id' => 2]);
    }

    public function test_getResidents_returns_only_tenant_residents()
    {
        Auth::login($this->tenant1User);
        
        // テナント1の利用者作成
        $tenant1Resident = Resident::factory()->create([
            'tenant_id' => 1,
            'unit_id' => $this->tenant1Unit->id
        ]);
        
        // テナント2の利用者作成（アクセス不可であるべき）
        Resident::factory()->create([
            'tenant_id' => 2,
            'unit_id' => $this->tenant2Unit->id
        ]);
        
        $residents = $this->residentService->getResidents();
        
        $this->assertCount(1, $residents);
        $this->assertEquals($tenant1Resident->id, $residents->first()->id);
    }

    public function test_getResident_throws_exception_for_different_tenant()
    {
        Auth::login($this->tenant1User);
        
        // テナント2の利用者作成
        $tenant2Resident = Resident::factory()->create([
            'tenant_id' => 2,
            'unit_id' => $this->tenant2Unit->id
        ]);
        
        $this->expectException(TenantViolationException::class);
        $this->expectExceptionMessage("他のテナントのリソースにアクセスしようとしました。");
        
        $this->residentService->getResident($tenant2Resident->id);
    }

    public function test_createResident_validates_unit_tenant_boundary()
    {
        Auth::login($this->tenant1User);
        
        $request = new ResidentStoreRequest();
        $request->merge([
            'name' => 'テスト利用者',
            'unit_id' => $this->tenant2Unit->id, // 異なるテナントの部署
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $this->expectException(TenantViolationException::class);
        
        $this->residentService->createResident($request);
    }

    public function test_updateResident_validates_tenant_boundary()
    {
        Auth::login($this->tenant1User);
        
        $tenant2Resident = Resident::factory()->create([
            'tenant_id' => 2,
            'unit_id' => $this->tenant2Unit->id
        ]);
        
        $request = new ResidentUpdateRequest();
        $request->merge(['name' => '更新テスト']);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $this->expectException(TenantViolationException::class);
        
        $this->residentService->updateResident($request, $tenant2Resident->id);
    }

    public function test_deleteResident_validates_tenant_boundary()
    {
        Auth::login($this->tenant1User);
        
        $tenant2Resident = Resident::factory()->create([
            'tenant_id' => 2,
            'unit_id' => $this->tenant2Unit->id
        ]);
        
        $this->expectException(TenantViolationException::class);
        
        $this->residentService->deleteResident($tenant2Resident->id);
    }

    public function test_getUnitsForTenant_returns_only_tenant_units()
    {
        Auth::login($this->tenant1User);
        
        // テナント2の追加部署作成
        Unit::factory()->create(['tenant_id' => 2]);
        
        $units = $this->residentService->getUnitsForTenant();
        
        $this->assertCount(1, $units);
        $this->assertEquals($this->tenant1Unit->id, $units->first()->id);
        $this->assertEquals(1, $units->first()->tenant_id);
    }

    public function test_successful_resident_operations_within_same_tenant()
    {
        Auth::login($this->tenant1User);
        
        // 正常な作成テスト
        $request = new ResidentStoreRequest();
        $request->merge([
            'name' => 'テスト利用者',
            'unit_id' => $this->tenant1Unit->id,
            'meal_support' => '一部介助',
        ]);
        $request->setValidator(validator($request->all(), $request->rules()));
        
        $resident = $this->residentService->createResident($request);
        
        $this->assertEquals('テスト利用者', $resident->name);
        $this->assertEquals(1, $resident->tenant_id);
        
        // 正常な取得テスト
        $retrievedResident = $this->residentService->getResident($resident->id);
        $this->assertEquals($resident->id, $retrievedResident->id);
        
        // 正常な更新テスト
        $updateRequest = new ResidentUpdateRequest();
        $updateRequest->merge(['name' => '更新された利用者']);
        $updateRequest->setValidator(validator($updateRequest->all(), $updateRequest->rules()));
        
        $updatedResident = $this->residentService->updateResident($updateRequest, $resident->id);
        $this->assertEquals('更新された利用者', $updatedResident->name);
        
        // 正常な削除テスト
        $this->residentService->deleteResident($resident->id);
        $this->assertDatabaseMissing('residents', ['id' => $resident->id]);
    }
}