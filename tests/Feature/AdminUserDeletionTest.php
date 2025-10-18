<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class AdminUserDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 安全なメモリDBにマイグレーション
        $this->runSafeMigrations();
        // 暗号化キー（テスト用）
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);

        // 役割を事前作成（ミドルウェア内の User::role('admin') 参照対策）
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
    }

    private function createAdmin(string $tenantId = 'tenant-a'): User
    {
        $admin = User::factory()->create([
            'tenant_id' => $tenantId,
            'username_id' => 'admin_' . uniqid(),
        ]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_admin_can_delete_user_without_attachments(): void
    {
        $admin = $this->createAdmin('tenant-a');
        $target = User::factory()->create([
            'tenant_id' => 'tenant-a',
            'username_id' => 'user_' . uniqid(),
        ]);

        $this->actingAs($admin);
        $response = $this->delete(route('users.destroy', $target->id));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_deleting_user_with_attachments_sets_uploaded_by_to_null(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('ON DELETE SET NULL 検証はMySQLでのみ実行');
        }
        $admin = $this->createAdmin('tenant-a');
        $target = User::factory()->create([
            'tenant_id' => 'tenant-a',
            'username_id' => 'user_' . uniqid(),
        ]);

        // テナントを作成（attachments.tenant_id FK対策）
        \App\Models\Tenant::unguard();
        $tenant = new \App\Models\Tenant(['business_name' => '', 'tenant_domain_id' => '']);
        $tenant->id = 'tenant-a';
        $tenant->save();

        // 対象ユーザーがアップロードした添付を作成
        $attachment = Attachment::factory()->create([
            'tenant_id' => 'tenant-a',
            'uploaded_by' => $target->id,
        ]);

        $this->actingAs($admin);
        $response = $this->delete(route('users.destroy', $target->id));
        $response->assertRedirect(route('users.index'));

        // ユーザーは削除され、添付のuploaded_byはNULLになっていること
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'uploaded_by' => null,
        ]);
    }

    public function test_admin_cannot_delete_user_from_other_tenant(): void
    {
        $admin = $this->createAdmin('tenant-a');
        $otherTenantUser = User::factory()->create([
            'tenant_id' => 'tenant-b',
            'username_id' => 'user_' . uniqid(),
        ]);

        $this->actingAs($admin);
        $response = $this->delete(route('users.destroy', $otherTenantUser->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $otherTenantUser->id]);
    }

    public function test_regular_user_cannot_delete_user(): void
    {
        /** @var User $regular */
        $regular = User::factory()->create([
            'tenant_id' => 'tenant-a',
            'username_id' => 'user_regular_' . uniqid(),
        ]);
        $regular->assignRole('user');

        /** @var User $target */
        $target = User::factory()->create([
            'tenant_id' => 'tenant-a',
            'username_id' => 'user_' . uniqid(),
        ]);

        $this->actingAs($regular);
        $response = $this->delete(route('users.destroy', $target->id));
        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
