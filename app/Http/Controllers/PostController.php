<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
{
    $posts = Post::with('user', 'comments', 'likes')->paginate(10);
    
    // InertiaでForumコンポーネントをレンダリングし、データを渡す
    return inertia('Forum', [
        'posts' => $posts,
    ]);
}


    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'ログインしていません'], 401);
        }

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            Post::create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'message' => $validated['message'],
            ]);

            return response()->json(['status' => '投稿が完了しました']);
        } catch (\Exception $e) {
            return response()->json(['error' => '投稿に失敗しました'], 500);
        }
    }

    public function destroy($id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'ログインしていません'], 401);
        }

        try {
            Post::findOrFail($id)->delete();
            return response()->json(['status' => '投稿を削除しました']);
        } catch (\Exception $e) {
            return response()->json(['error' => '削除に失敗しました'], 500);
        }
    }
}
