<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTenantIdColumnsToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropPrimary(['id']); // 主キー制約を一時的に削除
            $table->string('id', 36)->change(); // idカラムを文字列型に変更
            $table->primary('id'); // 主キー制約を再追加
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->string('tenant_id', 36)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('tenant_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropPrimary(['id']); // 主キー制約を一時的に削除
            $table->unsignedBigInteger('id')->change(); // idカラムを元のデータ型に戻す
            $table->primary('id'); // 主キー制約を再追加
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->change();
        });
    }
}
