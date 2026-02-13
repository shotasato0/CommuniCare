<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // `users_username_id_unique` 制約を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_id_unique');  // インデックス名を指定
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元に戻す（`username_id` にユニーク制約を再設定）
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username_id');  // `username_id` にユニーク制約を再追加
        });
    }
};
