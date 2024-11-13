<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;

class PostController extends Controller
{

    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => $request->input('quoted_post_id') ? 'nullable|string|max:255' : 'required|string|max:255',
        'message' => 'required|string',
        'forum_id' => 'required|exists:forums,id',
        'quoted_post_id' => 'nullable|exists:posts,id',
    ]);

    $post = Post::create([
        'user_id' => auth()->id(),
        'title' => $validated['title'],
        'message' => $validated['message'],
        'forum_id' => $validated['forum_id'],
        'quoted_post_id' => $validated['quoted_post_id'] ?? null,
    ]);

    // 引用投稿の場合は Inertia::location、通常投稿の場合はリダイレクト
    if ($request->has('quoted_post_id')) {
        // Inertia::locationでリロードしつつ、フォーラムページへ
        return Inertia::location(route('forum.index', ['forum_id' => $validated['forum_id']]));
    } else {
        // 通常投稿はリダイレクトでフォーラムページへ
        return redirect()->route('forum.index');
    }
}

    public function destroy($id)
    {
        Post::findOrFail($id)->forceDelete();
        return redirect()->route('forum.index');
    }
}
