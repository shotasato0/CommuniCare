<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Comment\CommentStoreRequest;

class CommentController extends Controller
{
    public function store(CommentStoreRequest $request)
    {
        $validated = $request->validated();

        // 画像がアップロードされた場合は保存
        $imgPath = null;
        if ($request->hasFile('image')) {
            $imgPath = $request->file('image')->store('images', 'public');
        }

        $post = Post::find($validated['post_id']); // 投稿を取得

        // モデルを使用してコメントを作成
        $comment = Comment::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'post_id' => $validated['post_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'message' => $validated['message'],
            'forum_id' => $post->forum_id,
            'img' => $imgPath
        ]);

        $user = Auth::user();
        $redirectParams = ['forum_id' => $post->forum_id];
        
        // ユーザーが部署に所属している場合、active_unit_idも追加
        if ($user->unit_id) {
            $redirectParams['active_unit_id'] = $user->unit_id;
        }
        
        return redirect()->route('forum.index', $redirectParams)
            ->with('success', 'コメントを投稿しました。');
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
}
