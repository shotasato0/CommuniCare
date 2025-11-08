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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->index();
            $table->unsignedBigInteger('calendar_date_id');
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('schedule_type_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('memo')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // インデックス
            $table->index('calendar_date_id');
            $table->index('resident_id');
            $table->index('schedule_type_id');
            // 重複検知用の複合インデックス
            $table->index(['tenant_id', 'calendar_date_id', 'start_time']);

            // テナント内で同一日付・同一利用者・同一時間帯の重複を禁止
            // 注意: 同じ時間帯に異なる種別のスケジュールは許可しない（時間帯のみで重複禁止）
            $table->unique(['tenant_id', 'resident_id', 'calendar_date_id', 'start_time']);

            // 外部キー制約（MySQLのみ）
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('cascade');

                $table->foreign('calendar_date_id')
                    ->references('id')
                    ->on('calendar_dates')
                    ->onDelete('cascade');

                $table->foreign('resident_id')
                    ->references('id')
                    ->on('residents')
                    ->onDelete('cascade');

                $table->foreign('schedule_type_id')
                    ->references('id')
                    ->on('schedule_types')
                    ->onDelete('restrict');

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
