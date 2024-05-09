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
        Schema::table('nursing_homes', function (Blueprint $table) {
            $table->string('name')->unique()->change();  // 既存のカラムにユニーク制約を追加
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nursing_homes', function (Blueprint $table) {
            $table->dropUnique(['name']);  // ユニーク制約を削除
        });
    }
};

