<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQLのみ対象（テストはSQLiteのためスキップ）
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // 既存の外部キーを削除（名前はエラー文より既知）
        DB::statement('ALTER TABLE `attachments` DROP FOREIGN KEY `attachments_uploaded_by_foreign`');

        // カラムをNULL許可に変更
        DB::statement('ALTER TABLE `attachments` MODIFY `uploaded_by` BIGINT UNSIGNED NULL');

        // ON DELETE SET NULL で外部キーを再作成
        DB::statement('ALTER TABLE `attachments`
            ADD CONSTRAINT `attachments_uploaded_by_foreign`
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // 元の状態に戻す（RESTRICT相当）
        DB::statement('ALTER TABLE `attachments` DROP FOREIGN KEY `attachments_uploaded_by_foreign`');
        DB::statement('ALTER TABLE `attachments` MODIFY `uploaded_by` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `attachments`
            ADD CONSTRAINT `attachments_uploaded_by_foreign`
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)');
    }
};

