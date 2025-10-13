<?php

namespace Tests\Security;

use Exception;
use Tests\TestCase; // セキュリティ機構内蔵の基底クラス

/**
 * 危険な操作がテスト環境で実行できないことを検証する安全テスト
 * - APP_ENV=testing の強制
 * - SQLite + :memory: の強制
 * - 破壊的artisanコマンドのブロック
 */
class DangerousOperationTest extends TestCase
{
    public function test_environment_is_testing(): void
    {
        $this->assertSame('testing', env('APP_ENV'));
    }

    public function test_database_is_sqlite_memory(): void
    {
        $this->assertSame('sqlite', env('DB_CONNECTION'));
        $this->assertSame(':memory:', env('DB_DATABASE'));
    }

    /**
     * @dataProvider dangerousCommands
     */
    public function test_dangerous_artisan_commands_are_blocked(string $command): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/危険なコマンド/');

        // Tests\\TestCase::artisan を経由し、ブラックリストで例外が投げられることを検証
        $this->artisan($command);
    }

    public static function dangerousCommands(): array
    {
        return [
            ['migrate:fresh'],
            ['migrate:reset'],
            ['db:wipe'],
            ['migrate:rollback'],
        ];
    }
}

