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
        Schema::table('posts', function (Blueprint $table) {
            // title にインデックスを追加
            $table->index('title', 'title_index');
        });

        // message に部分インデックスを追加（最初の255文字）
        DB::statement('ALTER TABLE posts ADD INDEX message_index (message(255))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // title インデックスの削除
            $table->dropIndex('title_index');
        });

        // message インデックスの削除
        DB::statement('ALTER TABLE posts DROP INDEX message_index');
    }
};


