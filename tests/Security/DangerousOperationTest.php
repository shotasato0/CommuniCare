<?php

namespace Tests\Security;

use Tests\TestCase;

class DangerousOperationTest extends TestCase
{
    public function test_environment_is_testing(): void
    {
        $this->assertSame('testing', env('APP_ENV'));
        $this->assertSame('testing', config('app.env'));
    }

    public function test_database_is_sqlite_memory(): void
    {
        $this->assertSame('sqlite', env('DB_CONNECTION'));
        $this->assertSame(':memory:', env('DB_DATABASE'));

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    /**
     * @dataProvider dangerousCommandsProvider
     */
    public function test_dangerous_command_is_blocked(string $command): void
    {
        $this->expectException(\Exception::class);
        $this->artisan($command);
    }

    public static function dangerousCommandsProvider(): array
    {
        return [
            ['migrate:fresh'],
            ['migrate:reset'],
            ['db:wipe'],
            ['migrate:rollback'],
        ];
    }

    public function test_refresh_database_trait_detection_blocks_usage(): void
    {
        $this->expectException(\Exception::class);

        $dummy = new class extends TestCase {
            use \Illuminate\Foundation\Testing\RefreshDatabase;
        };

        // setUp は protected のためリフレクションで呼び出し
        $ref = new \ReflectionClass($dummy);
        $method = $ref->getMethod('setUp');
        $method->setAccessible(true);
        $method->invoke($dummy);
    }
}

