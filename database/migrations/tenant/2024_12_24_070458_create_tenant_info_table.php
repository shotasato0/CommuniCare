<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_info', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('business_name')->default('default_name');
            $table->string('tenant_domain_id')->default('default_domain');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_info');
    }
};