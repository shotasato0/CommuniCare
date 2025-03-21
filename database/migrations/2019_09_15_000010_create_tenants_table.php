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
            $table->uuid('id')->primary();  // UUIDの主キー
            $table->string('business_name')->nullable(false)->default('');  // 事業所名 (NULLを許可しない、デフォルト値を空文字に設定)
            $table->string('tenant_domain_id')->nullable(false)->default('');  // ドメインID (NULLを許可しない、デフォルト値を空文字に設定)
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
