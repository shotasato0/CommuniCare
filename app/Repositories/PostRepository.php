<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostRepository implements IPostRepository
{
    public function __construct(private Post $model)
    {
    }

    /**
     * 投稿を作成
     */
    public function create(array $data): Post
    {
        return $this->model->create($data);
    }

    /**
     * IDで投稿を取得（存在しない場合は例外をスロー）
     */
    public function findOrFail(int $id): Post
    {
        return $this->model->findOrFail($id);
    }

    /**
     * テナント境界チェック付きで投稿を取得
     */
    public function findByTenant(string $tenantId, int $id): ?Post
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->find($id);
    }

    /**
     * 投稿を完全削除（ソフトデリートを無視）
     */
    public function forceDelete(Post $post): bool
    {
        return $post->forceDelete();
    }

    /**
     * 引用している投稿のフラグを更新
     */
    public function updateQuotingPosts(int $postId): void
    {
        $this->model->where('quoted_post_id', $postId)
            ->update(['quoted_post_deleted' => true]);
    }

    /**
     * 特定のフォーラムの投稿を取得（ページネーション対応）
     */
    public function getByForum(int $forumId, string $tenantId, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->where('forum_id', $forumId)
            ->where('tenant_id', $tenantId)
            ->with([
                'user' => function ($query) use ($tenantId) {
                    $query->select('id', 'name', 'tenant_id')
                        ->where('tenant_id', $tenantId);
                },
                'quotedPost' => function ($query) use ($tenantId) {
                    $query->select('id', 'title', 'message', 'user_id', 'tenant_id')
                        ->where('tenant_id', $tenantId)
                        ->with(['user' => function ($query) use ($tenantId) {
                            $query->select('id', 'name', 'tenant_id')
                                ->where('tenant_id', $tenantId);
                        }]);
                },
                'attachments' => function ($query) use ($tenantId) {
                    $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                        ->where('tenant_id', $tenantId)
                        ->where('is_safe', true);
                },
                'comments' => function ($query) use ($tenantId) {
                    $query->select('id', 'post_id', 'user_id', 'message', 'tenant_id', 'created_at')
                        ->where('tenant_id', $tenantId)
                        ->with([
                            'user' => function ($query) use ($tenantId) {
                                $query->select('id', 'name', 'tenant_id')
                                    ->where('tenant_id', $tenantId);
                            },
                            'attachments' => function ($query) use ($tenantId) {
                                $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                    ->where('tenant_id', $tenantId)
                                    ->where('is_safe', true);
                            }
                        ])
                        ->latest()
                        ->limit(10); // コメント数制限
                },
                'likes' => function ($query) use ($tenantId) {
                    $query->select('id', 'post_id', 'user_id', 'tenant_id')
                        ->where('tenant_id', $tenantId);
                }
            ])
            ->withCount(['likes' => function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
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
     * 投稿の詳細情報を取得（テナント境界チェック・N+1対策）
     */
    public function getPostDetails(int $postId, string $tenantId): ?Post
    {
        return $this->model->where('id', $postId)
            ->where('tenant_id', $tenantId)
            ->with([
                'user' => function ($query) use ($tenantId) {
                    $query->select('id', 'name', 'email', 'tenant_id')
                        ->where('tenant_id', $tenantId);
                },
                'quotedPost' => function ($query) use ($tenantId) {
                    $query->select('id', 'title', 'message', 'user_id', 'tenant_id', 'created_at')
                        ->where('tenant_id', $tenantId)
                        ->with([
                            'user' => function ($query) use ($tenantId) {
                                $query->select('id', 'name', 'tenant_id')
                                    ->where('tenant_id', $tenantId);
                            },
                            'attachments' => function ($query) use ($tenantId) {
                                $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                    ->where('tenant_id', $tenantId)
                                    ->where('is_safe', true);
                            }
                        ]);
                },
                'attachments' => function ($query) use ($tenantId) {
                    $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id', 'created_at')
                        ->where('tenant_id', $tenantId)
                        ->where('is_safe', true)
                        ->orderBy('created_at', 'asc');
                },
                'comments' => function ($query) use ($tenantId) {
                    $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'created_at', 'updated_at')
                        ->where('tenant_id', $tenantId)
                        ->with([
                            'user' => function ($query) use ($tenantId) {
                                $query->select('id', 'name', 'tenant_id')
                                    ->where('tenant_id', $tenantId);
                            },
                            'attachments' => function ($query) use ($tenantId) {
                                $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                    ->where('tenant_id', $tenantId)
                                    ->where('is_safe', true);
                            },
                            'children' => function ($query) use ($tenantId) {
                                $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'created_at')
                                    ->where('tenant_id', $tenantId)
                                    ->with([
                                        'user' => function ($query) use ($tenantId) {
                                            $query->select('id', 'name', 'tenant_id')
                                                ->where('tenant_id', $tenantId);
                                        },
                                        'attachments' => function ($query) use ($tenantId) {
                                            $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_name', 'file_size', 'mime_type', 'file_type', 'tenant_id')
                                                ->where('tenant_id', $tenantId)
                                                ->where('is_safe', true);
                                        }
                                    ])
                                    ->latest();
                            }
                        ])
                        ->whereNull('parent_id') // トップレベルコメントのみ
                        ->latest();
                },
                'likes' => function ($query) use ($tenantId) {
                    $query->select('id', 'post_id', 'user_id', 'tenant_id', 'created_at')
                        ->where('tenant_id', $tenantId)
                        ->with(['user' => function ($query) use ($tenantId) {
                            $query->select('id', 'name', 'tenant_id')
                                ->where('tenant_id', $tenantId);
                        }]);
                }
            ])
            ->first();
    }

    /**
     * 投稿の添付ファイルを取得
     */
    public function getAttachment(int $attachmentId, Post $post): Attachment
    {
        return $post->attachments()->findOrFail($attachmentId);
    }
}
