<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\User;

class ForumController extends Controller
{
    public function index(Request $request)
{
    $search = $request->input('search');
    $posts = Post::with(['user', 'comments' => function ($query) {
        $query->whereNull('parent_id')->with(['children.user', 'user']);
    }])
    ->when($search, function ($query, $search) {
        return $query->where('title', 'like', '%' . $search . '%')
                     ->orWhere('message', 'like', '%' . $search . '%');
    })
    ->latest()
    ->paginate(5);

    $units = Unit::with('forum')->get(); // サイドバー用のユニット情報
    $users = User::all();

    return Inertia::render('Forum', [
        'posts' => $posts,
        'search' => $search,
        'units' => $units,
        'users' => $users,
    ]);
}

}
