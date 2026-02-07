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
        Schema::table('schedules', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['schedule_type_id']);
            // インデックスを削除
            $table->dropIndex(['schedule_type_id']);
            // カラムを削除
            $table->dropColumn('schedule_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // カラムを追加
            $table->unsignedBigInteger('schedule_type_id')->after('schedule_name');
            // インデックスを追加
            $table->index('schedule_type_id');
            // 外部キー制約を追加
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->foreign('schedule_type_id')
                    ->references('id')
                    ->on('schedule_types')
                    ->onDelete('restrict');
            }
        });
    }
};
