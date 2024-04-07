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
             // 既存の外部キー制約を削除
             $table->dropForeign(['post_id']);
             // 新しい外部キー制約を追加
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // 追加した外部キー制約を削除
            $table->dropForeign(['post_id']);
            // 元の外部キー制約を復元
            $table->foreign('post_id')->references('id')->on('posts');
        });
    }
};
