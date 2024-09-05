<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTenantDomainNameToTenantDomainIdInTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // nameフィールドをtenant_domain_idに変更
            $table->renameColumn('tenant_domain_name', 'tenant_domain_id');
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
            // tenant_domain_idフィールドをtenant_domain_nameに戻す
            $table->renameColumn('tenant_domain_id', 'tenant_domain_name');
        });
    }
}
