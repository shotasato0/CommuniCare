<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id(); // 主キー
            $table->unsignedBigInteger('unit_id')->nullable(); // ユニットID（外部キー）
            $table->unsignedBigInteger('forum_id')->nullable(); // 掲示板ID（外部キー）
            $table->string('name'); // 利用者名
            $table->text('meal_support')->nullable(); // 食事の支援
            $table->text('toilet_support')->nullable(); // 排泄介助について
            $table->text('bathing_support')->nullable(); // 入浴介助について
            $table->text('mobility_support')->nullable(); // 移動や歩行に関する情報
            $table->text('memo')->nullable(); // その他の備考
            $table->timestamps(); // 作成日時・更新日時

            // 外部キー制約
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('residents');
    }
};
