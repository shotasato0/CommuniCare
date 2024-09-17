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
            $table->morphs('likeable'); // likeable_id と likeable_type のカラムが作成される
            $table->timestamps();
        
            // ユニーク制約を追加 (ユーザーが同じ対象に複数回いいねできない)
            $table->unique(['user_id', 'likeable_id', 'likeable_type']);
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('likes');
    }
}


