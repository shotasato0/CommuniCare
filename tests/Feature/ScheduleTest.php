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
        
        $this->tenant2 = Tenant::create([
            'id' => 'tenant-2-' . uniqid(),
            'business_name' => 'Test Tenant 2',
            'tenant_domain_id' => 'test2',
            'data' => [],
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

    public function test_cannot_create_schedule_with_conflict(): void
    {
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        
        // 既存スケジュール作成
        $calendarDate = CalendarDate::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'date' => $date,
            ],
            [
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'is_holiday' => false,
            ]
        );
        
        Schedule::create([
            'tenant_id' => $this->tenant1->id,
            'calendar_date_id' => $calendarDate->id,
            'resident_id' => $this->resident1->id,
            'schedule_type_id' => $this->scheduleType1->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'created_by' => $this->user1->id,
        ]);
        
        // 重複スケジュール作成試行
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
        $this->actingAs($this->user1);
        
        $date = Carbon::today()->format('Y-m-d');
        $calendarDate = CalendarDate::firstOrCreate(
            [
                'tenant_id' => $this->tenant1->id,
                'date' => $date,
            ],
            [
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'is_holiday' => false,
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
        
        // tenant2のユーザーでアクセス試行
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
}
