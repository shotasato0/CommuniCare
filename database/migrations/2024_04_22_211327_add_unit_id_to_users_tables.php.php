<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitIdToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // unit_idカラムを追加し、unitsテーブルのidカラムとの外部キー制約を設定
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // 外部キー制約を削除し、unit_idカラムを削除
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
}

