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
    // トランザクションを利用して整合性を確保
    \DB::transaction(function () use ($id) {
        // 削除対象の投稿を取得
        $post = Post::findOrFail($id);

        // 削除対象の投稿を引用している投稿を取得
        $affectedPosts = Post::where('quoted_post_id', $post->id)->get();
        
        // デバッグ: 更新前の状態を確認
        \Log::info('Before update:', ['affectedPosts' => $affectedPosts->toArray()]);

        // 引用投稿のフラグを更新
        Post::where('quoted_post_id', $post->id)
            ->update(['quoted_post_deleted' => true]);

        // 更新後の投稿を取得
        $updatedPosts = Post::where('quoted_post_id', $post->id)->get();

        // デバッグ: 更新後の状態を確認
        \Log::info('After update:', ['updatedPosts' => $updatedPosts->toArray()]);

        // 削除対象の投稿を削除
        $post->forceDelete();
    });

    return app(ForumController::class)->index(request());
}


}
