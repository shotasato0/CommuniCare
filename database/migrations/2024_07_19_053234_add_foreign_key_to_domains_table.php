<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 既存の外部キー制約があれば削除
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        // tenant_id カラムの型を変更
        Schema::table('domains', function (Blueprint $table) {
            $table->uuid('tenant_id')->change();
        });

        // 外部キー制約を追加
        Schema::table('domains', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->bigInteger('tenant_id')->change();  // 元の型に戻す
        });
    }
}
