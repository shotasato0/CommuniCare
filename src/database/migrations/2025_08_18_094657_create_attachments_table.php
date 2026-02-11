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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            
            // ポリモーフィックリレーション（投稿・コメント等に対応）
            $table->string('attachable_type'); // App\Models\Post, App\Models\Comment
            $table->unsignedBigInteger('attachable_id');
            $table->index(['attachable_type', 'attachable_id'], 'attachable_index');
            
            // ファイル情報
            $table->string('original_name'); // 元のファイル名
            $table->string('file_name');     // 保存時のファイル名
            $table->string('file_path');     // ストレージパス
            $table->unsignedBigInteger('file_size'); // バイト単位
            $table->string('mime_type');     // application/pdf, image/jpeg等
            $table->enum('file_type', ['image', 'pdf', 'document', 'excel', 'text']); // ファイル種別
            
            // セキュリティ・メタデータ
            $table->string('tenant_id')->index(); // マルチテナント境界保護
            $table->unsignedBigInteger('uploaded_by'); // アップロードしたユーザー
            $table->string('hash', 64)->nullable(); // ファイルハッシュ（重複検出用）
            $table->boolean('is_safe')->default(true); // ウイルススキャン結果等
            
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->foreign('tenant_id')->references('id')->on('tenants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
