<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * GitHub Copilotレビュー対応: 前回のdownマイグレーション修正
     * 既に実行済みのマイグレーションのdownメソッドをアップデート
     */
    public function up(): void
    {
        // このマイグレーションは前回の修正を記録するためのもの
        // 実際の処理は不要（downマイグレーションの論理的修正のみ）
    }

    /**
     * Reverse the migrations.
     * 
     * GitHub Copilot指摘対応: NULL値存在時の安全なロールバック
     * uploaded_byにNULL値が存在する可能性を考慮し、
     * ロールバック時もnullableのまま維持する
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['uploaded_by']);
            
            // GitHub Copilot修正: カラムをnullableのまま維持
            // 理由: ユーザー削除後にNULL値が存在する可能性があるため
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
    }
};
