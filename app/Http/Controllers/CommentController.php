<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Unit;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'nullable|exists:comments,id', // 返信先のコメントIDは任意
            'message' => 'required|string|max:1000',
        ]);

        // 投稿から forum_id を取得
        $post = Post::find($validated['post_id']);
        $forumId = $post->forum_id;

        // コメントの作成
        $comment = new Comment();
        $comment->tenant_id = auth()->user()->tenant_id;
        $comment->post_id = $validated['post_id'];
        $comment->parent_id = $validated['parent_id'] ?? null;
        $comment->message = $validated['message'];
        $comment->user_id = Auth::id();
        $comment->forum_id = $forumId; // forum_idを設定
        $comment->save();

        // コメントに関連するユーザー情報をロード
        $comment->load('user');

        // ユニット情報の取得
        $units = Unit::all();

        // Inertiaレスポンスを返す
        return Inertia::render('Forum', [
            'units' => $units,
        ]);
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
