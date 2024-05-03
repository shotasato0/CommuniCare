<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth; // Authファサードをインポート

class PostController extends Controller
{
    
    public function showPostsPage()
    {
        return view('layouts.index');  // このview('index')はVueアプリケーションのマウントポイントとなるindex.blade.phpを指します
    }

    public function index()
{
    $posts = Post::with('comments')->orderBy('created_at', 'desc')->paginate(10);
    return response()->json($posts);
}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $post = new Post($request->all());
    $post->user_id = Auth::id();  // 現在認証されているユーザーのIDを設定
    $post->save();
    return response()->json($post, 201);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
{
    Post::findOrFail($id)->delete();
    return response()->json(null, 204);
}


    public function search(Request $request)
    {
        $search_message = '%' .addcslashes($request->search_message, '%_\\') . '%';

        $posts = Post::where('message', 'LIKE', $search_message)->orderBy('created_at', 'desc')->Paginate(5);

        // 検索結果を表示するビューを返す
        // compact関数は、指定された変数名に対応する変数の値を持つ連想配列を作成します。
        // この場合、'posts'変数の値をビューに渡すために使用されています。
        return view('layouts.index', compact('posts'));
    }

    public function forUnit($unitId)
{
    $posts = Post::where('unit_id', $unitId)->get();
    return response()->json($posts);
}
}