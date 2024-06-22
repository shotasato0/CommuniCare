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
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);  // 既存の外部キー制約を削除
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');  // 新しい外部キー制約を設定
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);  // 既存の外部キー制約を削除
            // 外部キーを再設定する場合、必要に応じて onDelete を指定する
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }
};
