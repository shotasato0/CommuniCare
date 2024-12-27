<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // tenants テーブルの作成
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();  // 自動インクリメントの主キー
            $table->string('business_name')->after('id');  // 事業所名
            $table->string('tenant_domain_id')->unique();  // ドメインID
            $table->timestamps();
            $table->json('data')->nullable();  // JSONデータ保存用
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // tenants テーブルの削除
        Schema::dropIfExists('tenants');
    }
};
