<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL以外は対象外
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // attachmentsテーブルが無い場合は終了
        if (!Schema::hasTable('attachments')) {
            return;
        }

        // 既存の外部キー制約の存在確認（information_schema）
        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = database()')
            ->where('TABLE_NAME', 'attachments')
            ->where('CONSTRAINT_NAME', 'attachments_uploaded_by_foreign')
            ->exists();

        if ($exists) {
            // すでに制約が存在する場合は何もしない（再追加禁止）
            return;
        }

        // カラムが無ければ追加（nullable unsignedBigInteger）
        if (!Schema::hasColumn('attachments', 'uploaded_by')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('uploaded_by')->nullable();
            });
        }

        // 外部キー（users.id, ON DELETE SET NULL）を名前付きで追加
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreign('uploaded_by', 'attachments_uploaded_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MySQL以外は対象外
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('attachments')) {
            return;
        }

        // 既存の外部キー制約の存在確認（information_schema）
        $exists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = database()')
            ->where('TABLE_NAME', 'attachments')
            ->where('CONSTRAINT_NAME', 'attachments_uploaded_by_foreign')
            ->exists();

        if ($exists) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropForeign('attachments_uploaded_by_foreign');
            });
        }
    }
};
