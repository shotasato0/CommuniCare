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

    Post::create([
        'user_id' => auth()->id(),
        'title' => $validated['title'],
        'message' => $validated['message'],
        'forum_id' => $validated['forum_id'],
        'quoted_post_id' => $validated['quoted_post_id'] ?? null,
    ]);

    return redirect()->route('forum.index');
}



    public function destroy($id)
    {
        Post::findOrFail($id)->forceDelete();
        return redirect()->route('forum.index');
    }
}
