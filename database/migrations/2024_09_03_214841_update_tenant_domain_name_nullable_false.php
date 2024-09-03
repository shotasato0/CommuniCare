<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTenantDomainNameNullableFalse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // tenant_domain_nameフィールドをnullable(false)に変更
            $table->string('tenant_domain_name')->nullable(false)->change();
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
            // tenant_domain_nameフィールドをnullable(true)に戻す
            $table->string('tenant_domain_name')->nullable()->change();
        });
    }
}

