<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('units', function (Blueprint $table) {
        // 既存のユニーク制約を削除
        $table->dropUnique(['name']);
        // 新しい複合ユニーク制約を追加
        $table->unique(['tenant_id', 'name']);
    });
}

    public function down()
{
    Schema::table('units', function (Blueprint $table) {
        // ロールバック時の処理
        $table->dropUnique(['tenant_id', 'name']);
        $table->unique(['name']);
    });
}
};
