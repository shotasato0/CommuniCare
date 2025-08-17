<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Unit;
use App\Models\Resident;
use App\Models\Forum;
use App\Models\Post;
use App\Services\ResidentService;
use App\Services\UnitService;
use App\Services\PostService;
use App\Services\ForumService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class ServicePerformanceTest extends TestCase
{
    // 注意: セキュリティ機構によりSQLiteメモリDBが強制される
    
    public function createApplication()
    {
        $app = parent::createApplication();
        
        // 通常のテストデータベースを使用（.env.testingの設定）
        return $app;
    }

    protected $tenant1User;
    protected $tenant2User;
    protected $residentService;
    protected $unitService;
    protected $postService;
    protected $forumService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 通常のテストデータベースを使用
        
        // テーブルが存在しない場合のみマイグレーション実行
        $this->ensureTablesExist();
        
        // テストテーブルのクリーンアップ（sessionsテーブル以外）
        $this->cleanupTestData();
        
        // テナントレコードを作成
        DB::table('tenants')->insert([
            'id' => '1',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::table('tenants')->insert([
            'id' => '2', 
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->tenant1User = User::factory()->create(['tenant_id' => '1']);
        $this->tenant2User = User::factory()->create(['tenant_id' => '2']);
        
        $this->residentService = new ResidentService();
        $this->unitService = new UnitService();
        $this->postService = new PostService();
        $this->forumService = new ForumService();
    }
    
    protected function ensureTablesExist(): void
    {
        // 必要なテーブルが存在するかチェック
        $requiredTables = ['users', 'units', 'forums', 'residents', 'tenants'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $missingTables[] = $table;
            }
        }
        
        // テーブル不足時の適切な処理とエラーメッセージ
        if (!empty($missingTables)) {
            $this->markTestSkipped(
                "🚨 テスト環境不備: 必要なテーブルが不足しています。\n" .
                "不足テーブル: " . implode(', ', $missingTables) . "\n" .
                "原因: SQLiteメモリDBではマイグレーションが未実行の可能性があります。\n" .
                "対処法: 'php artisan migrate --env=testing' を事前実行してください。\n" .
                "注意: CommuniCareV2のセキュリティポリシーにより、テスト実行時の自動マイグレーションは禁止されています。"
            );
        }
        
        // SQLite環境の制限事項を記録（テストレポートで確認可能）
        fwrite(STDERR, "⚠️  SQLite環境でのパフォーマンステスト実行中。MySQL環境と比較して測定精度が制限されます。\n");
    }
    
    protected function runSpecificMigrations(): void
    {
        // テストデータベースは既にマイグレーション済みのため何もしない
        // 必要な場合のみマイグレーションを実行
        if (!DB::getSchemaBuilder()->hasTable('users')) {
            $this->artisan('migrate', ['--force' => true]);
        }
    }
    
    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }
    
    protected function cleanupTestData(): void
    {
        // 外部キー制約処理（SQLite環境では不要）
        // CommuniCareV2のセキュリティ機構によりMySQLは使用不可
        
        // テナント関連データのクリーンアップ（セッションテーブル以外）
        // 注意: tenants、権限関連テーブルはセントラルDBの重要データのため除外
        $tables = [
            'likes', 'comments', 'posts', 'forums', 'residents', 'units', 'users'
            // セントラルDB保護: tenants, model_has_permissions, model_has_roles, role_has_permissions
        ];
        
        // 🚨 安全確認：SQLiteメモリDBでのみ実行
        if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->delete(); // truncate() の代わりに delete() を使用
                }
            }
        } else {
            throw new \Exception('🚨 セキュリティ違反: テストデータクリーンアップはSQLiteメモリDBでのみ実行可能です');
        }
        
        // 外部キー制約処理完了（SQLite環境では操作不要）
    }

    /**
     * 大量データセットアップ
     */
    protected function createLargeDataset(): void
    {
        // テナント1に大量データ作成
        $units = Unit::factory(20)->sequence(
            fn ($sequence) => ['name' => "ユニット{$sequence->index}_T1", 'tenant_id' => '1']
        )->create();
        
        foreach ($units as $unit) {
            // フォーラム作成
            Forum::factory()->create([
                'unit_id' => $unit->id,
                'tenant_id' => '1'
            ]);
            
            // 利用者を100人作成
            Resident::factory(100)->create([
                'unit_id' => $unit->id,
                'tenant_id' => '1'
            ]);
        }
        
        // テナント2に中程度データ作成
        $tenant2Units = Unit::factory(10)->sequence(
            fn ($sequence) => ['name' => "ユニット{$sequence->index}_T2", 'tenant_id' => '2']
        )->create();
        
        foreach ($tenant2Units as $unit) {
            Forum::factory()->create([
                'unit_id' => $unit->id,
                'tenant_id' => '2'
            ]);
            
            Resident::factory(50)->create([
                'unit_id' => $unit->id,
                'tenant_id' => '2'
            ]);
        }
    }

    /**
     * クエリ数測定
     */
    protected function countQueries(callable $callback): int
    {
        $queryCount = 0;
        
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });
        
        $callback();
        
        return $queryCount;
    }

    /**
     * メモリ使用量測定
     */
    protected function measureMemory(callable $callback): array
    {
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);
        
        $result = $callback();
        
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);
        
        return [
            'result' => $result,
            'memory_used' => $memoryAfter - $memoryBefore,
            'peak_memory' => $peakAfter - $peakBefore,
            'memory_before' => $memoryBefore,
            'memory_after' => $memoryAfter
        ];
    }

    /**
     * 実行時間測定
     */
    protected function measureExecutionTime(callable $callback): array
    {
        $startTime = microtime(true);
        
        $result = $callback();
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // ミリ秒
        
        return [
            'result' => $result,
            'execution_time_ms' => $executionTime
        ];
    }

    public function test_resident_service_large_dataset_performance()
    {
        $this->createLargeDataset();
        Auth::login($this->tenant1User);
        
        // ResidentService::getResidents() のパフォーマンステスト
        $queryCount = $this->countQueries(function() {
            $memoryResult = $this->measureMemory(function() {
                return $this->measureExecutionTime(function() {
                    return $this->residentService->getResidents();
                });
            });
            
            // メモリ使用量アサーション（50MB以下）
            $this->assertLessThanOrEqual(
                50 * 1024 * 1024,
                $memoryResult['memory_used'],
                "ResidentService メモリ使用量が50MBを超過"
            );
            
            // 実行時間アサーション（2秒以下）
            $this->assertLessThanOrEqual(
                2000,
                $memoryResult['result']['execution_time_ms'],
                "ResidentService 実行時間が2秒を超過"
            );
            
            // データ件数確認（テナント1のみ）
            $this->assertEquals(2000, $memoryResult['result']['result']->count());
        });
        
        // クエリ数アサーション（N+1問題チェック）
        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "ResidentService クエリ数が5を超過（N+1問題の可能性）"
        );
    }

    public function test_unit_service_large_dataset_performance()
    {
        $this->createLargeDataset();
        Auth::login($this->tenant1User);
        
        // UnitService::getUnitsWithForum() のパフォーマンステスト
        $queryCount = $this->countQueries(function() {
            $memoryResult = $this->measureMemory(function() {
                return $this->measureExecutionTime(function() {
                    return $this->unitService->getUnitsWithForum();
                });
            });
            
            // メモリ使用量アサーション（10MB以下）
            $this->assertLessThanOrEqual(
                10 * 1024 * 1024,
                $memoryResult['memory_used'],
                "UnitService メモリ使用量が10MBを超過"
            );
            
            // 実行時間アサーション（500ms以下）
            $this->assertLessThanOrEqual(
                500,
                $memoryResult['result']['execution_time_ms'],
                "UnitService 実行時間が500msを超過"
            );
            
            // データ件数確認（テナント1のみ）
            $this->assertEquals(20, $memoryResult['result']['result']->count());
        });
        
        // クエリ数アサーション（N+1問題チェック）
        $this->assertLessThanOrEqual(
            3,
            $queryCount,
            "UnitService クエリ数が3を超過（N+1問題の可能性）"
        );
    }

    public function test_tenant_boundary_isolation_performance()
    {
        $this->createLargeDataset();
        
        // テナント1としてログイン
        Auth::login($this->tenant1User);
        $tenant1Residents = $this->residentService->getResidents();
        
        // テナント2としてログイン
        Auth::login($this->tenant2User);
        $tenant2Residents = $this->residentService->getResidents();
        
        // テナント分離確認
        $this->assertEquals(2000, $tenant1Residents->count());
        $this->assertEquals(500, $tenant2Residents->count());
        
        // 異なるテナントのデータが混在していないか確認
        $tenant1OnlyIds = $tenant1Residents->pluck('tenant_id')->unique();
        $tenant2OnlyIds = $tenant2Residents->pluck('tenant_id')->unique();
        
        $this->assertEquals([1], $tenant1OnlyIds->toArray());
        $this->assertEquals([2], $tenant2OnlyIds->toArray());
    }

    public function test_memory_leak_prevention()
    {
        $this->createLargeDataset();
        Auth::login($this->tenant1User);
        
        $initialMemory = memory_get_usage(true);
        
        // 10回連続でサービスを実行
        for ($i = 0; $i < 10; $i++) {
            $this->residentService->getResidents();
            $this->unitService->getUnitsWithForum();
            
            // ガベージコレクション強制実行
            gc_collect_cycles();
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // メモリリーク検出（100MB以下の増加であることを確認）
        $this->assertLessThanOrEqual(
            100 * 1024 * 1024,
            $memoryIncrease,
            "メモリリークが検出されました: " . round($memoryIncrease / 1024 / 1024, 2) . "MB増加"
        );
    }

    public function test_concurrent_tenant_access_simulation()
    {
        $this->createLargeDataset();
        
        // 複数テナントの同時アクセスシミュレーション
        $results = [];
        
        for ($i = 0; $i < 5; $i++) {
            Auth::login($this->tenant1User);
            $results['tenant1'][] = $this->measureExecutionTime(function() {
                return $this->residentService->getResidents();
            });
            
            Auth::login($this->tenant2User);
            $results['tenant2'][] = $this->measureExecutionTime(function() {
                return $this->residentService->getResidents();
            });
        }
        
        // 各テナントの平均実行時間を確認
        $tenant1AvgTime = collect($results['tenant1'])->avg('execution_time_ms');
        $tenant2AvgTime = collect($results['tenant2'])->avg('execution_time_ms');
        
        $this->assertLessThanOrEqual(2000, $tenant1AvgTime);
        $this->assertLessThanOrEqual(2000, $tenant2AvgTime);
        
        // 結果の整合性確認
        foreach ($results['tenant1'] as $result) {
            $this->assertEquals(2000, $result['result']->count());
        }
        
        foreach ($results['tenant2'] as $result) {
            $this->assertEquals(500, $result['result']->count());
        }
    }
}