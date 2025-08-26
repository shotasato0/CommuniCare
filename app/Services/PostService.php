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
use Illuminate\Support\Facades\Log;

class PostService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    
    private AttachmentService $attachmentService;
    
    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }
    /**
     * 投稿を作成する
     */
    public function createPost(PostStoreRequest $request): Post
    {
        $validated = $request->validated();
        
        // DB トランザクションで投稿作成とファイル添付を安全に実行
        return DB::transaction(function () use ($validated, $request) {
            // レガシー画像アップロードの処理
            $imgPath = null;
            if ($request->hasFile('image') && !$request->hasFile('files')) {
                // レガシーシステム: 直接imgフィールドに保存
                $imgPath = $request->file('image')->store('images', 'public');
            }
            
            // 投稿を作成
            $post = Post::create([
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
     * 投稿を削除する（引用関係も適切に処理）
     */
    public function deletePost(int $postId): void
    {
        DB::transaction(function () use ($postId) {
            $post = Post::findOrFail($postId);
            
            // セキュリティチェック: 投稿の所有者またはテナントが一致するかチェック
            $this->validatePostOwnership($post);

            // この投稿を引用している投稿のフラグを更新
            $this->updateQuotingPosts($postId);

            // 投稿を完全削除
            $post->forceDelete();
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
     * 引用している投稿のフラグを更新
     */
    private function updateQuotingPosts(int $postId): void
    {
        Post::where('quoted_post_id', $postId)
            ->update(['quoted_post_deleted' => true]);
    }

    /**
     * 統一ファイル添付システムでファイルを処理
     */
    private function handleFileAttachments(PostStoreRequest $request, Post $post): void
    {
        Log::info('=== handleFileAttachments Debug ===', [
            'hasFile_image' => $request->hasFile('image'),
            'hasFile_files' => $request->hasFile('files'),
            'post_id' => $post->id
        ]);
        
        // レガシー画像フィールドの処理（後方互換性）
        if ($request->hasFile('image')) {
            Log::info('Calling uploadSingleFile for image');
            $this->attachmentService->uploadSingleFile(
                $request->file('image'),
                'App\\Models\\Post',
                $post->id
            );
        }
        
        // 新しい統一ファイル添付システム
        if ($request->hasFile('files')) {
            Log::info('Calling uploadFiles for files array', [
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
        
        $attachment = $post->attachments()->findOrFail($attachmentId);
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
        
        $query = Post::where('forum_id', $forumId)
            ->where('tenant_id', $currentUser->tenant_id) // テナント境界チェック追加
            ->with([
                'user' => function($query) use ($currentUser) {
                    $query->select('id', 'name', 'tenant_id')
                          ->where('tenant_id', $currentUser->tenant_id);
                },
                'quotedPost' => function($query) use ($currentUser) {
                    $query->select('id', 'title', 'message', 'user_id', 'tenant_id')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->with(['user' => function($query) use ($currentUser) {
                              $query->select('id', 'name', 'tenant_id')
                                    ->where('tenant_id', $currentUser->tenant_id);
                          }]);
                },
                'attachments' => function($query) use ($currentUser) {
                    $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->where('is_safe', true);
                },
                'comments' => function($query) use ($currentUser) {
                    $query->select('id', 'post_id', 'user_id', 'message', 'tenant_id', 'created_at')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->with([
                              'user' => function($query) use ($currentUser) {
                                  $query->select('id', 'name', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id);
                              },
                              'attachments' => function($query) use ($currentUser) {
                                  $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id)
                                        ->where('is_safe', true);
                              }
                          ])
                          ->latest()
                          ->limit(10); // コメント数制限
                },
                'likes' => function($query) use ($currentUser) {
                    $query->select('id', 'post_id', 'user_id', 'tenant_id')
                          ->where('tenant_id', $currentUser->tenant_id);
                }
            ])
            ->withCount(['likes' => function($query) use ($currentUser) {
                $query->where('tenant_id', $currentUser->tenant_id);
            }])
            ->select('id', 'user_id', 'forum_id', 'title', 'message', 'quoted_post_id', 'tenant_id', 'like_count', 'created_at', 'updated_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        return $query->latest()->paginate($perPage);
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
        
        $post = Post::where('id', $postId)
            ->where('tenant_id', $currentUser->tenant_id) // テナント境界チェック
            ->with([
                'user' => function($query) use ($currentUser) {
                    $query->select('id', 'name', 'email', 'tenant_id')
                          ->where('tenant_id', $currentUser->tenant_id);
                },
                'quotedPost' => function($query) use ($currentUser) {
                    $query->select('id', 'title', 'message', 'user_id', 'tenant_id', 'created_at')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->with([
                              'user' => function($query) use ($currentUser) {
                                  $query->select('id', 'name', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id);
                              },
                              'attachments' => function($query) use ($currentUser) {
                                  $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id)
                                        ->where('is_safe', true);
                              }
                          ]);
                },
                'attachments' => function($query) use ($currentUser) {
                    $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id', 'created_at')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->where('is_safe', true)
                          ->orderBy('created_at', 'asc');
                },
                'comments' => function($query) use ($currentUser) {
                    $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'created_at', 'updated_at')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->with([
                              'user' => function($query) use ($currentUser) {
                                  $query->select('id', 'name', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id);
                              },
                              'attachments' => function($query) use ($currentUser) {
                                  $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                        ->where('tenant_id', $currentUser->tenant_id)
                                        ->where('is_safe', true);
                              },
                              'children' => function($query) use ($currentUser) {
                                  $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'created_at')
                                        ->where('tenant_id', $currentUser->tenant_id)
                                        ->with([
                                            'user' => function($query) use ($currentUser) {
                                                $query->select('id', 'name', 'tenant_id')
                                                      ->where('tenant_id', $currentUser->tenant_id);
                                            },
                                            'attachments' => function($query) use ($currentUser) {
                                                $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                                      ->where('tenant_id', $currentUser->tenant_id)
                                                      ->where('is_safe', true);
                                            }
                                        ])
                                        ->latest();
                              }
                          ])
                          ->whereNull('parent_id') // トップレベルコメントのみ
                          ->latest();
                },
                'likes' => function($query) use ($currentUser) {
                    $query->select('id', 'post_id', 'user_id', 'tenant_id', 'created_at')
                          ->where('tenant_id', $currentUser->tenant_id)
                          ->with(['user' => function($query) use ($currentUser) {
                              $query->select('id', 'name', 'tenant_id')
                                    ->where('tenant_id', $currentUser->tenant_id);
                          }]);
                }
            ])
            ->first();

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
     */
    public function getPostById(int $postId): Post
    {
        return Post::findOrFail($postId);
    }
}