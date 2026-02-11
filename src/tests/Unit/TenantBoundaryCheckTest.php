<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Traits\TenantBoundaryCheckTrait;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Support\Facades\Auth;
use Tests\Support\UserStub;
use Illuminate\Database\Eloquent\Model;

class TenantBoundaryCheckTest extends TestCase
{
    private DummyChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new DummyChecker();
    }

    public function test_validate_tenant_boundary_throws_on_mismatch(): void
    {
        $this->mockAuthUser(1, 10);

        $resource = new DummyModel(1, 20);

        $this->expectException(TenantViolationException::class);
        $this->checker->check($resource);
    }

    public function test_validate_tenant_boundary_passes_on_match(): void
    {
        $this->mockAuthUser(2, 10);

        $resource = new DummyModel(2, 10);

        // 例外が投げられないことを確認
        $this->checker->check($resource);
        $this->assertTrue(true);
    }

    public function test_validate_multiple_tenant_boundaries_passes_all(): void
    {
        $this->mockAuthUser(3, 7);

        $resources = [
            new DummyModel(10, 7),
            new DummyModel(11, 7),
            new DummyModel(12, 7),
        ];

        $this->checker->checkMultiple($resources);
        $this->assertTrue(true);
    }

    public function test_validate_related_resource_throws_on_missing_relation(): void
    {
        $this->mockAuthUser(4, 5);
        $parent = new DummyModel(100, 5);
        // 関連を設定しない（missing）

        $this->expectException(TenantViolationException::class);
        $this->checker->checkRelated($parent, 'child');
    }

    public function test_validate_related_resource_throws_on_mismatch(): void
    {
        $this->mockAuthUser(5, 5);
        $parent = new DummyModel(200, 5);
        $child = new DummyModel(201, 9); // テナント不一致
        $parent->child = $child;

        $this->expectException(TenantViolationException::class);
        $this->checker->checkRelated($parent, 'child');
    }

    private function mockAuthUser(int $id, int $tenantId): void
    {
        Auth::shouldReceive('user')->andReturn(new UserStub($id, $tenantId));
    }
}

class DummyChecker
{
    use TenantBoundaryCheckTrait;

    public function check(Model $resource, ?int $expectedTenantId = null): void
    {
        $this->validateTenantBoundary($resource, $expectedTenantId);
    }

    public function checkMultiple(array $resources): void
    {
        $this->validateMultipleTenantBoundaries($resources);
    }

    public function checkRelated(Model $resource, string $path): void
    {
        $this->validateRelatedResourceTenant($resource, $path);
    }
}

class DummyModel extends Model
{
    public $timestamps = false;

    public function __construct(public ?int $id = null, public ?int $tenant_id = null)
    {
        parent::__construct();
    }
}
