<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Starting user icons migration to attachments system');
        
        $attachmentService = app(AttachmentService::class);
        
        // 既存のiconフィールドがあるユーザーを取得
        $users = User::whereNotNull('icon')->get();
        
        foreach ($users as $user) {
            try {
                $iconPath = $user->icon;
                $filePath = null;
                
                // ファイルパスの正規化と存在チェック
                if (str_starts_with($iconPath, 'images/profiles/')) {
                    $filePath = public_path($iconPath);
                } elseif (!str_starts_with($iconPath, '/')) {
                    // storage/app/public/icons/ にあると想定
                    $storagePath = 'icons/' . $iconPath;
                    if (Storage::disk('public')->exists($storagePath)) {
                        $filePath = Storage::disk('public')->path($storagePath);
                    }
                }
                
                if ($filePath && file_exists($filePath)) {
                    // ファイル情報を取得
                    $originalName = basename($filePath);
                    $mimeType = mime_content_type($filePath);
                    $fileSize = filesize($filePath);
                    
                    // AttachmentServiceを使用してAttachmentレコードを作成
                    $attachmentData = [
                        'original_name' => $originalName,
                        'file_name' => $originalName,
                        'file_path' => $filePath, // 一時的なファイルパス
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'file_type' => 'image',
                    ];
                    
                    // Attachmentレコードを作成
                    $attachment = new Attachment();
                    $attachment->fill($attachmentData);
                    $attachment->tenant_id = $user->tenant_id;
                    
                    // ポリモーフィック関係の設定
                    $user->attachments()->save($attachment);
                    
                    // AttachmentServiceを使用してファイルを適切な場所にコピー
                    $attachmentService->moveFileToFinalLocation($attachment, $filePath);
                    
                    Log::info("Successfully migrated icon for user {$user->id}: {$iconPath}");
                } else {
                    Log::warning("Icon file not found for user {$user->id}: {$iconPath}");
                }
                
            } catch (\Exception $e) {
                Log::error("Failed to migrate icon for user {$user->id}: " . $e->getMessage());
            }
        }
        
        Log::info('Completed user icons migration to attachments system');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移行を戻す場合の処理
        // Attachmentテーブルからユーザーアイコンを削除
        Attachment::whereHasMorph('attachable', [User::class])->delete();
        
        Log::info('Reversed user icons migration');
    }
};