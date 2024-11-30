<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyForeignKeyOnPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            // 既存の外部キーを削除
            $table->dropForeign(['quoted_post_id']);

            // 新しい外部キーを追加
            $table->foreign('quoted_post_id')
                ->references('id')
                ->on('posts')
                ->nullOnDelete(); // カスケード削除を防ぎ、NULLにする
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            // 新しい外部キーを削除
            $table->dropForeign(['quoted_post_id']);

            // 元の外部キーを再追加
            $table->foreign('quoted_post_id')
                ->references('id')
                ->on('posts')
                ->cascadeOnDelete(); // 元のカスケード設定に戻す
        });
    }
}
