<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');  // 自動インクリメントの主キー
            $table->string('domain', 255)->unique();  // ドメイン名、ユニーク制約付き
            $table->unsignedBigInteger('tenant_id');  // tenantsテーブルのidに合わせてunsignedBigInteger型
            
            $table->timestamps();
            
            // 外部キー制約の追加
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // tenant_id にユニークインデックスを追加
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
