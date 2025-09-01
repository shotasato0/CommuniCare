<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // postsテーブルの作成
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quoted_post_id')->nullable();  // 引用元の投稿ID
            $table->boolean('quoted_post_deleted')->default(false);  // 引用元削除フラグ
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();  // タイトルをnullableに
            $table->text('message');
            $table->integer('like_count')->default(0);
            $table->timestamps();
            $table->softDeletes();  // ソフトデリート対応

            // インデックスの追加
            $table->index('title', 'title_index');
        });

        // messageカラムの部分インデックスは、MySQL環境でのみ
        // 2025_08_29_120000_add_message_index_to_posts_table.php にて付与

        // 外部キーの追加（テーブル作成後に実施）
        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('quoted_post_id')
                ->references('id')
                ->on('posts')
                ->nullOnDelete();  // 引用元削除時はNULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // 外部キーの削除
            $table->dropForeign(['quoted_post_id']);
            $table->dropColumn(['quoted_post_id', 'quoted_post_deleted']);
        });

        Schema::dropIfExists('posts');
    }
}
