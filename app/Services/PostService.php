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

class PostService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    /**
     * 投稿を作成する
     */
    public function createPost(PostStoreRequest $request): Post
    {
        $validated = $request->validated();
        $imgPath = $this->handleImageUpload($request);

        return Post::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'message' => $validated['message'],
            'forum_id' => $validated['forum_id'],
            'quoted_post_id' => $validated['quoted_post_id'] ?? null,
            'img' => $imgPath,
            'tenant_id' => Auth::user()->tenant_id,
        ]);
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
     * 画像アップロードを処理
     */
    private function handleImageUpload(PostStoreRequest $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('images', 'public');
    }

    /**
     * 特定のフォーラムの投稿を取得（ページネーション対応）
     */
    public function getPostsByForum(int $forumId, ?string $search = null, int $perPage = 5)
    {
        $query = Post::where('forum_id', $forumId)
            ->with(['user', 'quotedPost', 'comments', 'likes'])
            ->withCount('likes');

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
     * 投稿の詳細情報を取得
     */
    public function getPostDetails(int $postId): ?Post
    {
        return Post::with([
            'user', 
            'quotedPost.user', 
            'comments.user', 
            'comments.children.user',
            'likes.user'
        ])->find($postId);
    }

    /**
     * IDによる投稿取得（リダイレクト用の基本情報）
     */
    public function getPostById(int $postId): Post
    {
        return Post::findOrFail($postId);
    }
}