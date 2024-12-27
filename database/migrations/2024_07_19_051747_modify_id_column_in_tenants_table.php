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
            $table->dropForeign('users_tenant_id_foreign');
        });

        // id カラムの変更を実行
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            // 新しい id カラムの定義
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
        // ロールバック時の処理
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_tenant_id_foreign');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->id(); // 元の auto-increment の id に戻す
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
}
