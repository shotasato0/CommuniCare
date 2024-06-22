<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth; // Authファサードをインポート
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    Log::info('PostController::index 呼び出し');
    Log::info('インデックス内の現在のセッションID:', ['id' => Session::getId()]);
    Log::info('インデックス内の現在のテナント:', ['tenant' => tenant()]);

    $posts = Post::orderBy('created_at', 'desc')->paginate(5);
    $comments = Comment::all();
    return view('layouts.index', compact('posts', 'comments'));
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
        $post = new Post;
        
         // ユーザー入力から受け取ったデータと、現在認証されているユーザーのIDを使用してデータを保存
        $post->saveWithUser($request->except('_token'), Auth::id());
    
        $post->save();
    
        return redirect('/index');
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
    public function destroy(string $id)
    {
        // スレッド情報をデータベースから削除
       $Post = Post::find($id)->delete();
       return redirect('/index');
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

    public function forUnit(Unit $unit)
    {
    return $unit->posts;
    }
}