<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        // ユーザー情報を含めた投稿データを取得
        $posts = Post::with('user')->latest()->get();
        return inertia('Forum', [
            'posts' => $posts,
        ]);
    }

    public function store(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // 投稿者のuser_idと投稿内容を保存
        $post = Post::create([
            'user_id' => auth()->id(),  // ログイン中のユーザーのIDを保存
            'title' => $validated['title'],
            'message' => $validated['message'],
        ]);

        // 正しいInertiaレスポンスで新しい投稿を返す
        return Inertia::render('Forum', [
            'post' => $post,  // 新しい投稿をレスポンスとして返す
        ]);
    }

    public function destroy($id)
    {
        Post::findOrFail($id)->delete();
        return redirect()->route('forum.index');
    }
}
