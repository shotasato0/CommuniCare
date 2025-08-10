<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Post\PostStoreRequest;

class PostService
{
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
        $currentUser = Auth::user();
        
        if ($post->user_id !== $currentUser->id && $post->tenant_id !== $currentUser->tenant_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('この投稿を削除する権限がありません。');
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
        $currentUser = Auth::user();
        
        return $post->user_id === $currentUser->id || 
               $currentUser->hasRole('admin');
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
}