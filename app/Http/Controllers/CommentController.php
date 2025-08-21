<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Comment\CommentStoreRequest;

class CommentController extends Controller
{
    public function store(CommentStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $post = Post::find($validated['post_id']); // 投稿を取得

            // 旧システム：画像アップロード処理
            $imgPath = null;
            if ($request->hasFile('img')) {
                $imgPath = $request->file('img')->store('images', 'public');
            }

            // コメント作成
            $comment = Comment::create([
                'tenant_id' => Auth::user()->tenant_id,
                'user_id' => Auth::id(),
                'post_id' => $validated['post_id'],
                'parent_id' => $validated['parent_id'] ?? null,
                'message' => $validated['message'],
                'forum_id' => $post->forum_id,
                'img' => $imgPath
            ]);

            // 新Attachmentシステム：添付ファイルの関連付け
            if (!empty($validated['attachment_ids'])) {
                $this->attachFilesToComment($comment, $validated['attachment_ids']);
            }

            $user = Auth::user();
            $redirectParams = ['forum_id' => $post->forum_id];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
                ->with('success', 'コメントを投稿しました。');
        });
    }

    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return redirect()->back()->withErrors(['comment_not_found' => 'コメントが見つかりません']);
        }

        $comment->delete();

        return redirect()->back();
    }

    /**
     * 添付ファイルをコメントに関連付ける
     */
    private function attachFilesToComment(Comment $comment, array $attachmentIds): void
    {
        $currentUser = Auth::user();
        
        // 添付ファイルのバリデーション
        $attachments = Attachment::whereIn('id', $attachmentIds)
            ->where('tenant_id', $currentUser->tenant_id)
            ->get();

        if ($attachments->count() !== count($attachmentIds)) {
            throw new \InvalidArgumentException('指定された添付ファイルの一部が見つからないか、アクセス権限がありません。');
        }

        // 各添付ファイルの関連付けを更新
        foreach ($attachments as $attachment) {
            $attachment->update([
                'attachable_type' => Comment::class,
                'attachable_id' => $comment->id,
            ]);
        }
    }
}
