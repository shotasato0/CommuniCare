<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\Attachment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IPostRepository
{
    /**
     * 投稿を作成
     *
     * @param array $data
     * @return Post
     */
    public function create(array $data): Post;

    /**
     * IDで投稿を取得（存在しない場合は例外をスロー）
     *
     * @param int $id
     * @return Post
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Post;

    /**
     * テナント境界チェック付きで投稿を取得
     *
     * @param string $tenantId
     * @param int $id
     * @return Post|null
     */
    public function findByTenant(string $tenantId, int $id): ?Post;

    /**
     * 投稿を完全削除（ソフトデリートを無視）
     *
     * @param Post $post
     * @return bool
     */
    public function forceDelete(Post $post): bool;

    /**
     * 引用している投稿のフラグを更新
     *
     * @param int $postId
     * @return void
     */
    public function updateQuotingPosts(int $postId): void;

    /**
     * 特定のフォーラムの投稿を取得（ページネーション対応）
     *
     * @param int $forumId
     * @param string $tenantId
     * @param string|null $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByForum(int $forumId, string $tenantId, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * 投稿の詳細情報を取得（テナント境界チェック・N+1対策）
     *
     * @param int $postId
     * @param string $tenantId
     * @return Post|null
     */
    public function getPostDetails(int $postId, string $tenantId): ?Post;

    /**
     * 投稿の添付ファイルを取得
     *
     * @param int $attachmentId
     * @param Post $post
     * @return Attachment
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAttachment(int $attachmentId, Post $post): Attachment;
}
