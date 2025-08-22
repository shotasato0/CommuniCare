<?php

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
        // uploaded_byカラムをnullableに変更
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
        
        // SET NULLオプション付きで外部キー制約を追加
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreign('uploaded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['uploaded_by']);
            
            // NOT NULLに戻す
            $table->unsignedBigInteger('uploaded_by')->nullable(false)->change();
        });
    }
};
