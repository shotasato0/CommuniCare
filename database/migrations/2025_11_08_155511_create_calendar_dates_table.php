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
        Schema::create('calendar_dates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->after('id')->index();
            $table->date('date')->after('tenant_id');
            $table->tinyInteger('day_of_week')->after('date')->comment('0=日, 1=月, ..., 6=土');
            $table->boolean('is_holiday')->default(false)->after('day_of_week');
            $table->string('holiday_name')->nullable()->after('is_holiday');
            $table->timestamps();

            // テナント内で日付の一意性を保証
            $table->unique(['tenant_id', 'date']);
            // 日付範囲検索用のインデックス
            $table->index('date');

            // 外部キー制約（MySQLのみ）
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_dates');
    }
};
