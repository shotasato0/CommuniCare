<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminDeletesGuestUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 安全なメモリDBに対してのみマイグレーションを実行
        $this->runSafeMigrations();

        // 暗号化キーをテスト用に設定（.env.testingを読まない実行パス対策）
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
    }

    public function test_admin_can_delete_guest_user(): void
    {
        // 前提: 管理者ロールを用意
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // 同一テナントの管理者ユーザーを作成
        $admin = User::factory()->create([
            'tenant_id' => 'tenant-1',
            'username_id' => 'admin_1',
        ]);
        $admin->assignRole('admin');

        // 同一テナントのゲストユーザーを作成（guest_session_id あり）
        $guest = User::factory()->create([
            'tenant_id' => 'tenant-1',
            'username_id' => 'guest_1',
            'guest_session_id' => 'guest-session-xyz',
        ]);

        // 管理者として認証し、ゲストユーザー削除エンドポイントを叩く
        $this->actingAs($admin);

        $response = $this->delete(route('users.destroy', $guest->id));

        $response->assertRedirect(route('users.index'));

        // ゲストユーザーが物理的に削除されていることを確認
        $this->assertDatabaseMissing('users', [
            'id' => $guest->id,
        ]);
    }
}
