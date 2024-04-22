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
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name');
            $table->index('name'); // nameフィールドにインデックスを追加
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('birth_date');
            $table->index('birth_date'); // birth_dateフィールドにインデックスを追加
            $table->string('postal_code')->nullable(); // 郵便番号
            $table->string('prefecture')->nullable();// 都道府県
            $table->string('city'); // 市区町村
            $table->string('address1'); // 番地
            $table->string('address2')->nullable(); // 建物名・部屋番号など
            $table->enum('nursing_care_level', ['level1', 'level2', 'level3', 'level4', 'level5']);
            $table->string('memo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
