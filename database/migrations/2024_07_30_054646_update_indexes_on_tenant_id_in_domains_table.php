<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIndexesOnTenantIdInDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('domains', function (Blueprint $table) {
            // 既存の外部キー制約を削除
            $table->dropForeign(['tenant_id']);
            
            // 既存のインデックスを削除（インデックス名を使用）
            $table->dropIndex('domains_tenant_id_foreign');
            
            // tenant_idにユニークインデックスを追加
            $table->unique('tenant_id');

            // 外部キー制約を再追加
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('domains', function (Blueprint $table) {
            // ユニークインデックスを削除
            $table->dropUnique(['tenant_id']);
            
            // 元のインデックスを追加（インデックス名を使用）
            $table->index('tenant_id', 'domains_tenant_id_foreign');

            // 外部キー制約を再追加
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }
}



