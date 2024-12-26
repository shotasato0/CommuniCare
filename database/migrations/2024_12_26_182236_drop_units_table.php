<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // セントラル環境で units テーブルを削除
        Schema::dropIfExists('units');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 必要であれば再作成
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
};
