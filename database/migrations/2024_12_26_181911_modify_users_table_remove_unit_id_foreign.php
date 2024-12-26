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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_id')) {
                // 外部キーが存在する場合は削除
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable();

            // 必要であれば再度外部キーを追加
            // $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }
};
