<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTenantsTableAddBusinessNameAndRenameNameColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // business_nameフィールドを追加
            $table->string('business_name')->after('id')->nullable();

            // nameフィールドをtenant_domain_nameに変更
            $table->renameColumn('name', 'tenant_domain_name');
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
            // business_nameフィールドを削除
            $table->dropColumn('business_name');

            // tenant_domain_nameフィールドをnameに戻す
            $table->renameColumn('tenant_domain_name', 'name');
        });
    }
}
