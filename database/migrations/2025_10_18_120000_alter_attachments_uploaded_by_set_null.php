<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 本番(MySQL)のみ適用。SQLite等のテスト環境では実行しない
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // 既存の uploaded_by 外部キー制約名を動的に特定
        $fk = DB::selectOne(<<<SQL
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attachments'
              AND COLUMN_NAME = 'uploaded_by'
              AND REFERENCED_TABLE_NAME = 'users'
            LIMIT 1
        SQL);

        if ($fk && isset($fk->name)) {
            // 既存FKを削除
            DB::statement('ALTER TABLE `attachments` DROP FOREIGN KEY `'.str_replace('`','',$fk->name).'`');
        }

        // カラムをNULL許可に変更
        DB::statement('ALTER TABLE `attachments` MODIFY `uploaded_by` BIGINT UNSIGNED NULL');

        // 既存の孤児レコードを整理（参照先ユーザーがいない場合はNULL化）
        DB::statement('UPDATE `attachments` a LEFT JOIN `users` u ON u.id = a.uploaded_by 
            SET a.uploaded_by = NULL 
            WHERE a.uploaded_by IS NOT NULL AND u.id IS NULL');

        // ON DELETE SET NULL で外部キーを再作成（名前はデフォルトに合わせる）
        DB::statement('ALTER TABLE `attachments`
            ADD CONSTRAINT `attachments_uploaded_by_foreign`
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // 現行のFKを削除
        $fk = DB::selectOne(<<<SQL
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'attachments'
              AND COLUMN_NAME = 'uploaded_by'
              AND REFERENCED_TABLE_NAME = 'users'
            LIMIT 1
        SQL);
        if ($fk && isset($fk->name)) {
            DB::statement('ALTER TABLE `attachments` DROP FOREIGN KEY `'.str_replace('`','',$fk->name).'`');
        }

        // 既にNULL値が存在する場合、ダウングレードを明示的に拒否（安全側）
        $nullCount = DB::table('attachments')->whereNull('uploaded_by')->count();
        if ($nullCount > 0) {
            throw new \RuntimeException('Down migration aborted: attachments.uploaded_by contains NULL values. Clean or reassign before reverting.');
        }

        // NOT NULL に戻す
        DB::statement('ALTER TABLE `attachments` MODIFY `uploaded_by` BIGINT UNSIGNED NOT NULL');

        // RESTRICT相当で再作成
        DB::statement('ALTER TABLE `attachments`
            ADD CONSTRAINT `attachments_uploaded_by_foreign`
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)');
    }
};
