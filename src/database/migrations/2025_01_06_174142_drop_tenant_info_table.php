<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // tenant_infoテーブルを削除
        Schema::dropIfExists('tenant_info');
    }

    public function down()
    {
        // 必要に応じて再作成する場合の処理
        Schema::create('tenant_info', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('business_name')->default('default_name');
            $table->string('tenant_domain_id')->default('default_domain');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }
};
