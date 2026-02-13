<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Post\PostStoreRequest;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\PostOwnershipException;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;
use App\Services\AttachmentService;
use App\Repositories\IPostRepository;
use App\Facades\Logs;

class PostService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    
    private AttachmentService $attachmentService;
    private IPostRepository $postRepository;
    
    public function __construct(
        AttachmentService $attachmentService,
        IPostRepository $postRepository
    ) {
        $this->attachmentService = $attachmentService;
        $this->postRepository = $postRepository;
    }
    /**
     * 投稿を作成する
     */
    public function createPost(PostStoreRequest $request): Post
    {
        $validated = $request->validated();
        
        // DB トランザクションで投稿作成とファイル添付を安全に実行
        return DB::transaction(function () use ($validated, $request) {
            // レガシー画像アップロードの処理（私有メソッドに委譲）
            $imgPath = $this->handleImageUpload($request);
            
            // 投稿を作成
            $post = $this->postRepository->create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'message' => $validated['message'],
                'forum_id' => $validated['forum_id'],
                'quoted_post_id' => $validated['quoted_post_id'] ?? null,
                'img' => $imgPath, // レガシー画像パス
                'tenant_id' => Auth::user()->tenant_id,
            ]);
            
            // 統一ファイル添付システムでファイルを処理（新システムの場合のみ）
            if ($request->hasFile('files')) {
                $this->handleFileAttachments($request, $post);
            }
            
            return $post;
        });
    }

    /**
     * レガシー画像アップロード（後方互換用）
     */
    private function handleImageUpload(PostStoreRequest $request): ?string
    {
        if ($request->hasFile('image') && !$request->hasFile('files')) {
            return $request->file('image')->store('images', 'public');
        }
        return null;
    }

    /**
     * 投稿を削除する（引用関係も適切に処理）
     */
    public function deletePost(int $postId): void
    {
        DB::transaction(function () use ($postId) {
            $currentUser = Auth::user();
            $post = $this->postRepository->findByTenant($currentUser->tenant_id, $postId);
            
            if (!$post) {
                throw new TenantViolationException(
                    currentTenantId: $currentUser->tenant_id,
                    resourceTenantId: '',
                    resourceType: 'post',
                    resourceId: $postId
                );
            }
            
            // セキュリティチェック: 投稿の所有者またはテナントが一致するかチェック
            $this->validatePostOwnership($post);

            // この投稿を引用している投稿のフラグを更新
            $this->postRepository->updateQuotingPosts($postId);

            // 添付ファイル（統一システム）を物理削除
            foreach ($post->attachments as $attachment) {
                $this->attachmentService->deleteAttachment($attachment);
            }

            // レガシー画像（img フィールド）があれば物理削除
            if (!empty($post->img)) {
                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($post->img);
                } catch (\Throwable $e) {
                    Logs::warning('Failed to delete legacy post image', [
                        'post_id' => $post->id,
                        'img' => $post->img,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 投稿を完全削除
            $this->postRepository->forceDelete($post);
        });
    }

    /**
     * 投稿の所有権を検証
     */
    private function validatePostOwnership(Post $post): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // まずテナント境界をチェック（必須条件）
        if ($post->tenant_id !== $currentUser->tenant_id) {
            throw new TenantViolationException(
                currentTenantId: $currentUser->tenant_id,
                resourceTenantId: $post->tenant_id,
                resourceType: 'post',
                resourceId: $post->id
            );
        }
        
        // 同じテナント内で投稿の所有者または管理者権限をチェック
        $isAdmin = $currentUser->hasRole('admin');
        if ($post->user_id !== $currentUser->id && !$isAdmin) {
            throw new PostOwnershipException(
                userId: $currentUser->id,
                postId: $post->id,
                postOwnerId: $post->user_id,
                operation: 'delete',
                isAdmin: $isAdmin
            );
        }
    }


    /**
     * 統一ファイル添付システムでファイルを処理
     */
    private function handleFileAttachments(PostStoreRequest $request, Post $post): void
    {
        if (config('attachments.debug_log')) Logs::info('=== handleFileAttachments Debug ===', [
            'hasFile_image' => $request->hasFile('image'),
            'hasFile_files' => $request->hasFile('files'),
            'post_id' => $post->id
        ]);
        
        // レガシー画像フィールドの処理（後方互換性）
        if ($request->hasFile('image')) {
            if (config('attachments.debug_log')) Logs::info('Calling uploadSingleFile for image');
            $this->attachmentService->uploadSingleFile(
                $request->file('image'),
                'App\\Models\\Post',
                $post->id
            );
        }
        
        // 新しい統一ファイル添付システム
        if ($request->hasFile('files')) {
            if (config('attachments.debug_log')) Logs::info('Calling uploadFiles for files array', [
                'files_count' => count($request->file('files'))
            ]);
            $this->attachmentService->uploadFiles(
                $request->file('files'),
                'App\\Models\\Post',
                $post->id
            );
        }
    }
    
    /**
     * 既存の投稿にファイルを追加
     */
    public function addAttachmentsToPost(Post $post, array $files): array
    {
        // テナント境界チェック
        $this->validateTenantAccess($post);
        
        return $this->attachmentService->uploadFiles($files, 'App\\Models\\Post', $post->id);
    }
    
    /**
     * 投稿からファイルを削除
     */
    public function removeAttachmentFromPost(Post $post, int $attachmentId): void
    {
        // テナント境界チェック
        $this->validateTenantAccess($post);
        
        $attachment = $this->postRepository->getAttachment($attachmentId, $post);
        $this->attachmentService->deleteAttachment($attachment);
    }
    
    /**
     * テナントアクセス検証
     */
    private function validateTenantAccess(Post $post): void
    {
        $currentTenantId = Auth::user()->tenant_id;
        
        if ($post->tenant_id !== $currentTenantId) {
            throw new TenantViolationException(
            currentTenantId: $currentTenantId,
            resourceTenantId: $post->tenant_id,
            resourceType: 'post',
            resourceId: $post->id
            );
        }
    }

    /**
     * 特定のフォーラムの投稿を取得（ページネーション対応）
     */
    public function getPostsByForum(int $forumId, ?string $search = null, int $perPage = 5)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return $this->postRepository->getByForum($forumId, $currentUser->tenant_id, $search, $perPage);
    }

    /**
     * ユーザーが投稿を削除できるかチェック
     */
    public function canDeletePost(Post $post): bool
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        
        // ユーザーが認証されていない場合は削除不可
        if (!$currentUser) {
            return false;
        }
        
        // 投稿の所有者は削除可能
        if ($post->user_id === $currentUser->id) {
            return true;
        }
        
        // 管理者権限を持つ場合は削除可能
        return $currentUser->hasRole('admin');
    }

    /**
     * 投稿の詳細情報を取得（テナント境界チェック強化・N+1対策）
     */
    public function getPostDetails(int $postId): ?Post
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $post = $this->postRepository->getPostDetails($postId, $currentUser->tenant_id);

        // テナント境界チェック後にログ記録
        if ($post) {
            $this->auditAction('post_view', [
                'post_id' => $postId,
                'post_title' => $post->title
            ]);
        }

        return $post;
    }

    /**
     * IDによる投稿取得（リダイレクト用の基本情報）
     * テナント境界チェック付き
     */
    public function getPostById(int $postId): Post
    {
        $currentUser = Auth::user();
        $post = $this->postRepository->findByTenant($currentUser->tenant_id, $postId);
        
        if (!$post) {
            throw new TenantViolationException(
                currentTenantId: $currentUser->tenant_id,
                resourceTenantId: '',
                resourceType: 'post',
                resourceId: $postId
            );
        }
        
        return $post;
    }

    /**
     * リダイレクトパラメータを構築
     *
     * 認証済みユーザーのコンテキストで呼び出す想定。未認証の場合は forum_id のみ返す。
     *
     * @param Post $post
     * @return array
     */
    public function buildRedirectParams(Post $post): array
    {
        $redirectParams = [
            'forum_id' => $post->forum_id,
        ];

        $user = Auth::user();
        if ($user && $user->unit_id) {
            $redirectParams['active_unit_id'] = $user->unit_id;
        }

        return $redirectParams;
    }
}
