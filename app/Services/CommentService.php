<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Comment\CommentStoreRequest;
use App\Exceptions\Custom\TenantViolationException;
use App\Services\AttachmentService;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;

class CommentService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    
    private AttachmentService $attachmentService;
    
    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * コメントを作成する
     */
    public function createComment(CommentStoreRequest $request): Comment
    {
        $validated = $request->validated();
        $post = Post::find($validated['post_id']);
        
        // テナント境界チェック
        $this->validateTenantAccess($post);
        
        // DB トランザクションでコメント作成とファイル添付を安全に実行
        return DB::transaction(function () use ($validated, $request, $post) {
            // コメントを作成（imgフィールドは削除）
            $comment = Comment::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'post_id' => $validated['post_id'],
                'parent_id' => $validated['parent_id'] ?? null,
                'message' => $validated['message'],
                'forum_id' => $post->forum_id,
            ]);
            
            // 統一ファイル添付システムでファイルを処理
            $this->handleFileAttachments($request, $comment);
            
            return $comment;
        });
    }

    /**
     * コメントを削除する
     */
    public function deleteComment(int $commentId): void
    {
        DB::transaction(function () use ($commentId) {
            $comment = Comment::findOrFail($commentId);
            
            // テナント境界チェック
            $this->validateTenantAccess($comment);
            
            // 所有権チェック
            $this->validateCommentOwnership($comment);

            // 添付ファイルも削除
            foreach ($comment->attachments as $attachment) {
                $this->attachmentService->deleteAttachment($attachment);
            }

            // コメントを削除
            $comment->delete();
        });
    }

    /**
     * 統一ファイル添付システムでファイルを処理
     */
    private function handleFileAttachments(CommentStoreRequest $request, Comment $comment): void
    {
        // レガシー画像フィールドの処理（後方互換性）
        if ($request->hasFile('image')) {
            $this->attachmentService->uploadSingleFile(
                $request->file('image'),
                $comment,
                'image'
            );
        }
        
        // 新しい統一ファイル添付システム
        if ($request->hasFile('files')) {
            $this->attachmentService->uploadFiles(
                $request->file('files'),
                $comment
            );
        }
    }
    
    /**
     * 既存のコメントにファイルを追加
     */
    public function addAttachmentsToComment(Comment $comment, array $files): array
    {
        // テナント境界チェック
        $this->validateTenantAccess($comment);
        
        return $this->attachmentService->uploadFiles($files, $comment);
    }
    
    /**
     * コメントからファイルを削除
     */
    public function removeAttachmentFromComment(Comment $comment, int $attachmentId): void
    {
        // テナント境界チェック
        $this->validateTenantAccess($comment);
        
        $attachment = $comment->attachments()->findOrFail($attachmentId);
        $this->attachmentService->deleteAttachment($attachment);
    }
    
    /**
     * テナントアクセス検証（Post用）
     */
    private function validateTenantAccess($model): void
    {
        $currentTenantId = Auth::user()->tenant_id;
        
        if ($model->tenant_id !== $currentTenantId) {
            $modelType = class_basename($model);
            throw new TenantViolationException(
                "テナント境界違反: 他のテナントの{$modelType}にアクセスしようとしました。",
                [
                    'user_tenant_id' => $currentTenantId,
                    'resource_tenant_id' => $model->tenant_id,
                    'resource_type' => $modelType,
                    'resource_id' => $model->id,
                    'user_id' => Auth::id()
                ]
            );
        }
    }
    
    /**
     * コメントの所有権を検証
     */
    private function validateCommentOwnership(Comment $comment): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // コメントの所有者または管理者権限をチェック
        $isAdmin = $currentUser->hasRole('admin');
        if ($comment->user_id !== $currentUser->id && !$isAdmin) {
            throw new \Exception('コメントの削除権限がありません。');
        }
    }
}