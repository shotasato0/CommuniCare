<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLikesTable extends Migration
{
    public function up()
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('likeable');  // ポリモーフィックリレーション（対象は投稿かコメント）
            $table->timestamps();

            // ユニーク制約を追加（同じユーザーが同じ対象に複数回いいねできない）
            $table->unique(['user_id', 'likeable_id', 'likeable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('likes');
    }
}

