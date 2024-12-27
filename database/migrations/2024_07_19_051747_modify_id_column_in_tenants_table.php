<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyIdColumnInTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 外部キー制約を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        // users テーブルの tenant_id カラムの型を変更
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->change();
        });

        // id カラムの変更を実行
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->uuid('id')->primary();
        });

        // 外部キー制約を再作成
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // 外部キー制約を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        // users テーブルの tenant_id を元の型に戻す
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('tenant_id')->change();
        });

        // tenants テーブルの id を元の型に戻す
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->id();  // 元の auto-increment の id に戻す
        });

        // 外部キー制約を再作成
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
}
