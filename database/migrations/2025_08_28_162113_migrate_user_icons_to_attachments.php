<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Starting user icons migration to attachments system');
        
        // 既存のiconフィールドがあるユーザーを取得
        $users = User::whereNotNull('icon')->get();
        
        foreach ($users as $user) {
            try {
                $iconPath = $user->icon;
                $sourceFilePath = null;
                
                // ファイルパスの正規化と存在チェック
                if (str_starts_with($iconPath, 'images/profiles/')) {
                    $sourceFilePath = public_path($iconPath);
                } elseif (!str_starts_with($iconPath, '/')) {
                    // storage/app/public/icons/ にあると想定
                    $storagePath = 'icons/' . $iconPath;
                    if (Storage::disk('public')->exists($storagePath)) {
                        $sourceFilePath = Storage::disk('public')->path($storagePath);
                    }
                }
                
                if ($sourceFilePath && file_exists($sourceFilePath)) {
                    // ファイル情報を取得
                    $originalName = basename($sourceFilePath);
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $mimeType = mime_content_type($sourceFilePath);
                    $fileSize = filesize($sourceFilePath);
                    $hash = hash_file('sha256', $sourceFilePath);
                    
                    // AttachmentService準拠の安全なファイル名生成
                    $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'icon';
                    $fileName = $safeName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                    
                    // AttachmentService準拠のストレージパス
                    $destinationPath = 'attachments/images/' . $fileName;
                    
                    // ファイルをStorage::disk('public')にコピー
                    $fileContent = file_get_contents($sourceFilePath);
                    Storage::disk('public')->put($destinationPath, $fileContent);
                    
                    // 保存確認
                    if (!Storage::disk('public')->exists($destinationPath)) {
                        throw new \RuntimeException('ファイルのコピーに失敗しました');
                    }
                    
                    // Attachmentレコード作成
                    $attachment = Attachment::create([
                        'attachable_type' => User::class,
                        'attachable_id' => $user->id,
                        'original_name' => $originalName,
                        'file_name' => $fileName,
                        'file_path' => $destinationPath,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'file_type' => 'image',
                        'tenant_id' => $user->tenant_id,
                        'uploaded_by' => $user->id, // 自分自身をアップロード者とする
                        'hash' => $hash,
                        'is_safe' => true // 既存ファイルは安全とみなす
                    ]);
                    
                    Log::info("Successfully migrated icon for user {$user->id}: {$iconPath} -> {$destinationPath}");
                } else {
                    Log::warning("Icon file not found for user {$user->id}: {$iconPath}");
                }
                
            } catch (\Exception $e) {
                Log::error(
                    "Failed to migrate icon for user {$user->id}",
                    [
                        'iconPath' => $iconPath ?? null,
                        'sourceFilePath' => $sourceFilePath ?? null,
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]
                );
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
