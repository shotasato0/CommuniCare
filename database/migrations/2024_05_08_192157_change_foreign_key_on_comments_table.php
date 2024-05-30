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
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);  // 既存の外部キー制約を削除
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');  // 新しい外部キー制約を設定
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // 新しい外部キー制約を削除
            $table->dropForeign(['user_id']);
            // 既存の外部キー制約を復元
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
