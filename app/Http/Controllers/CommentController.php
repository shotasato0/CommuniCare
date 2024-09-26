<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;  // Commentモデル
use App\Models\Post;     // Postモデル
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

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

        // コメントの作成
        $comment = new Comment();
        $comment->post_id = $validated['post_id'];
        $comment->parent_id = $validated['parent_id'] ?? null; // 返信コメントの場合はparent_idを設定
        $comment->message = $validated['message'];
        $comment->user_id = Auth::id(); // ログインユーザーのIDを設定
        $comment->save();

          // コメントに関連するユーザー情報をロードする
        $comment->load('user');

         // メンションのための全ユーザーリストを取得
         $users = User::all(['id', 'name']); // 必要なフィールドだけを取得

         // Inertiaレスポンスを返す
        return Inertia::render('Forum', [
            'users' => $users,
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
