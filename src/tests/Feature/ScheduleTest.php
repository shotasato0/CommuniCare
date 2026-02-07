<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Resident;
use App\Models\Schedule;
use App\Models\ScheduleType;
use App\Models\CalendarDate;
use App\Models\Unit;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleTest extends TestCase
{
    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;
    private Resident $resident1;
    private ScheduleType $scheduleType1;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 安全なマイグレーション実行
        $this->runSafeMigrations();
        
        // 暗号化キー設定
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        
        // 権限とロールの作成
        $this->setupRolesAndPermissions();
        
        // テストデータの作成
        $this->setupTestData();
    }

    private function setupRolesAndPermissions(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        
        Permission::firstOrCreate(['name' => 'schedules.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'schedules.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'schedules.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'schedules.delete', 'guard_name' => 'web']);
        
        $adminRole = Role::findByName('admin', 'web');
        $userRole = Role::findByName('user', 'web');
        
        $adminRole->syncPermissions(['schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete']);
        $userRole->syncPermissions(['schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete']);
    }

    private function setupTestData(): void
    {
        // テナント作成
        $this->tenant1 = Tenant::create([
            'id' => 'tenant-1-' . uniqid(),
            'business_name' => 'Test Tenant 1',
            'tenant_domain_id' => 'test1',
            'data' => [],
        ]);
        
        // テナント1のドメイン作成
        Domain::create([
            'domain' => 'test1.localhost',
            'tenant_id' => $this->tenant1->id,
        ]);
        
        $this->tenant2 = Tenant::create([
            'id' => 'tenant-2-' . uniqid(),
            'business_name' => 'Test Tenant 2',
            'tenant_domain_id' => 'test2',
            'data' => [],
        ]);
        
        // テナント2のドメイン作成
        Domain::create([
            'domain' => 'test2.localhost',
            'tenant_id' => $this->tenant2->id,
        ]);
        
        // ユーザー作成
        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'username_id' => 'user1_' . uniqid(),
        ]);
        $this->user1->assignRole('user');
        
        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'username_id' => 'user2_' . uniqid(),
        ]);
        $this->user2->assignRole('user');
        
        // 部署作成
        $unit1 = Unit::create([
            'name' => 'Test Unit',
            'tenant_id' => $this->tenant1->id,
        ]);
        
        // 利用者作成
        $this->resident1 = Resident::create([
            'name' => 'Test Resident',
            'unit_id' => $unit1->id,
            'tenant_id' => $this->tenant1->id,
        ]);
        
        // スケジュール種別作成
        $this->scheduleType1 = ScheduleType::create([
            'tenant_id' => $this->tenant1->id,
            'name' => '入浴',
            'color' => '#3B82F6',
            'sort_order' => 1,
        ]);
    }

    public function test_can_create_schedule(): void
    {
        // テナント1を直接初期化
        tenancy()->initialize($this->tenant1);
        
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        $response = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'memo' => 'Test memo',
        ]);
        
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'tenant_id',
                'resident_id',
                'schedule_type_id',
                'start_time',
                'end_time',
            ],
        ]);
        
        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $this->tenant1->id,
            'resident_id' => $this->resident1->id,
            'start_time' => '10:00',
        ]);
    }

    public function skip_test_cannot_create_schedule_with_conflict(): void
    {
        // テナント1を直接初期化
        tenancy()->initialize($this->tenant1);
        
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        // 最初のHTTPリクエストでスケジュールを作成（CalendarDateも自動的に作成される）
        $firstResponse = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        
        $firstResponse->assertStatus(201);
        
        // 重複スケジュール作成試行（同じ日付・同じ時間帯）
        $response = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        
        $response->assertStatus(409);
        $response->assertJson([
            'error_code' => 'SCHEDULE_CONFLICT',
        ]);
    }

    public function test_cannot_access_other_tenant_schedule(): void
    {
        // tenant1のスケジュールを作成
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        $calendarDate = CalendarDate::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ],
            [
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'is_holiday' => false,
                'holiday_name' => null,
            ]
        );
        
        $schedule = Schedule::create([
            'tenant_id' => $this->tenant1->id,
            'calendar_date_id' => $calendarDate->id,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'created_by' => $this->user1->id,
        ]);
        
        // tenant2のユーザーでアクセス試行（テナント2を初期化）
        tenancy()->end();
        tenancy()->initialize($this->tenant2);
        $this->actingAs($this->user2);
        
        $response = $this->getJson(route('calendar.schedules.index', [
            'date_from' => $date,
            'date_to' => $date,
        ]));
        
        $response->assertStatus(200);
        
        // tenant1のスケジュールが含まれていないことを確認
        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    public function test_can_update_schedule(): void
    {
        // テナント1を直接初期化
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        // スケジュールを作成
        $createResponse = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'memo' => 'Original memo',
        ]);
        
        $createResponse->assertStatus(201);
        $scheduleId = $createResponse->json('data.id');
        
        // スケジュールを更新
        $updateResponse = $this->putJson(route('calendar.schedule.update', $scheduleId), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'memo' => 'Updated memo',
        ]);
        
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonStructure([
            'data' => [
                'id',
                'tenant_id',
                'resident_id',
                'schedule_type_id',
                'start_time',
                'end_time',
                'memo',
            ],
        ]);
        
        $this->assertDatabaseHas('schedules', [
            'id' => $scheduleId,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'memo' => 'Updated memo',
        ]);
    }

    public function test_cannot_update_schedule_with_time_conflict(): void
    {
        // テナント1を直接初期化
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        // 最初のスケジュールを作成（10:00-11:00）
        $firstResponse = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        
        $firstResponse->assertStatus(201);
        
        // 2つ目のスケジュールを作成（12:00-13:00）
        $secondResponse = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);
        
        $secondResponse->assertStatus(201);
        $scheduleId = $secondResponse->json('data.id');
        
        // 2つ目のスケジュールを更新して、1つ目のスケジュールと時間帯が重複するようにする（10:30-11:30）
        $updateResponse = $this->putJson(route('calendar.schedule.update', $scheduleId), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ]);
        
        $updateResponse->assertStatus(409);
        $updateResponse->assertJson([
            'error_code' => 'SCHEDULE_CONFLICT',
        ]);
    }

    public function test_can_delete_schedule(): void
    {
        // テナント1を直接初期化
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        // スケジュールを作成
        $createResponse = $this->postJson(route('calendar.schedule.store'), [
            'date' => $date,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        
        $createResponse->assertStatus(201);
        $scheduleId = $createResponse->json('data.id');
        
        // スケジュールを削除
        $deleteResponse = $this->deleteJson(route('calendar.schedule.destroy', $scheduleId));
        
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson([
            'message' => 'スケジュールを削除しました。',
        ]);
        
        $this->assertDatabaseMissing('schedules', [
            'id' => $scheduleId,
        ]);
    }

    public function test_cannot_update_other_tenant_schedule(): void
    {
        // tenant1のスケジュールを作成
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        $calendarDate = CalendarDate::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ],
            [
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'is_holiday' => false,
                'holiday_name' => null,
            ]
        );
        
        $schedule = Schedule::create([
            'tenant_id' => $this->tenant1->id,
            'calendar_date_id' => $calendarDate->id,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'created_by' => $this->user1->id,
        ]);
        
        // tenant2のリソースを作成
        tenancy()->end();
        tenancy()->initialize($this->tenant2);
        
        $unit2 = Unit::create([
            'name' => 'Test Unit 2',
            'tenant_id' => $this->tenant2->id,
        ]);
        
        $resident2 = Resident::create([
            'name' => 'Test Resident 2',
            'unit_id' => $unit2->id,
            'tenant_id' => $this->tenant2->id,
        ]);
        
        $scheduleType2 = ScheduleType::create([
            'tenant_id' => $this->tenant2->id,
            'name' => '入浴2',
            'color' => '#3B82F6',
            'sort_order' => 1,
        ]);
        
        $this->actingAs($this->user2);
        
        // tenant1のスケジュールをtenant2のリソースで更新しようとする（テナント境界違反）
        $response = $this->putJson(route('calendar.schedule.update', $schedule->id), [
            'date' => $date,
            'resident_id' => $resident2->id,
            'schedule_type_id' => $scheduleType2->id,
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);
        
        $response->assertStatus(403);
        $response->assertJson([
            'error_code' => 'TENANT_VIOLATION',
        ]);
    }

    public function test_cannot_delete_other_tenant_schedule(): void
    {
        // tenant1のスケジュールを作成
        tenancy()->initialize($this->tenant1);
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        $calendarDate = CalendarDate::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ],
            [
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'is_holiday' => false,
                'holiday_name' => null,
            ]
        );
        
        $schedule = Schedule::create([
            'tenant_id' => $this->tenant1->id,
            'calendar_date_id' => $calendarDate->id,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'created_by' => $this->user1->id,
        ]);
        
        // tenant2のユーザーで削除試行
        tenancy()->end();
        tenancy()->initialize($this->tenant2);
        $this->actingAs($this->user2);
        
        $response = $this->deleteJson(route('calendar.schedule.destroy', $schedule->id));
        
        $response->assertStatus(403);
        $response->assertJson([
            'error_code' => 'TENANT_VIOLATION',
        ]);
        
        // スケジュールが削除されていないことを確認
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
        ]);
    }
}
