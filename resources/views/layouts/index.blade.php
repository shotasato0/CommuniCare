<x-app-layout>
    <div class="w-11/12 max-w-screen-md m-auto">
        {{-- タイトル --}}
        <h1 class="text-xl font-bold mt-5">{{ env('app_name') }}</h1>

        {{-- 入力フォーム --}}
        <div class="bg-white rounded-md mt-5 p-3">
            <form action="{{ route('posts.store') }}" method="POST">
                @csrf
                <div class="flex mt-2">
                    <p class="font-bold">件名</p>
                    <input class="border rounded px-2 ml-2 flex-auto" type="text" name="title" required>
                </div>
                <div class="flex flex-col mt-2">
                    <p class="font-bold">本文</p>
                    <textarea class="border rounded px-2" name="message" required></textarea>
                </div>
                <div class="flex justify-end mt-2">
                    <input class="my-2 px-2 py-1 rounded bg-blue-300 text-blue-900 font-bold link-hover cursor-pointer"
                        type="submit" value="投稿">
                </div>
            </form>
        </div>
        {{-- 検索フォーム --}}
        <div class="bg-white rounded-md mt-3 p-3">
            <form action="{{ route('posts.search') }}" method="post">
                @csrf
                <div class="mx-1 flex">
                    <input class="border rounded px-2 flex-auto" type="text" name="search_message" required>
                    <input class="ml-2 px-2 py-1 rounded bg-gray-500 text-white font-bold link-hover cursor-pointer"
                        type="submit" value="検索">
                </div>
            </form>
        </div>

        {{-- ページネーション --}}
        <p class="mt-5">{{ $posts->links() }}</p>

        {{-- 投稿 --}}
        @foreach ($posts as $post)
            <div class="bg-white rounded-md mt-1 mb-5 p-3">
                {{-- スレッド --}}
                <div>
                    <p class="mb-2 text-xs">{{ $post->created_at }} ＠{{ $post->user->name }}</p>
                    <p class="mb-2 text-xl font-bold">{{ $post->title }}</p>
                    <p class="mb-2">{{ $post->message }}</p>
                </div>
                {{-- ボタン --}}
                <div class="flex mt-5 items-center">
                    {{-- 返信 --}}
                    <form class="flex items-center flex-auto" action="{{ route('comment.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="post_id" value="{{ $post->id }}">
                        <input class="border rounded px-4 h-10 flex-auto" type="text" name="message"
                            placeholder="Comment" required>
                        <input class="h-10 px-4 ml-2 rounded bg-green-600 text-white font-bold cursor-pointer"
                            type="submit" value="返信">
                    </form>
                    {{-- 削除 --}}
                    <form action="{{ route('posts.destroy', ['post' => $post->id]) }}" method="post"
                        class="flex items-center">
                        @csrf
                        @method('DELETE')
                        <input class="h-10 px-4 ml-2 rounded bg-red-500 text-white font-bold cursor-pointer"
                            type="submit" value="削除" onclick="Check(event)">
                    </form>
                </div>

                {{-- 返信 --}}
                <hr class="mt-2 m-auto">
                <div class="flex justify-end">
                    <div class="w-11/12">
                        @foreach ($post->comments as $comment)
                            <div>
                                <p class="mt-2 text-xs">{{ $comment->created_at }} ＠{{ $comment->user->name }}</p>
                                <p class="my-2 text-sm">
                                    {{ $comment->message }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- ページネーション --}}
        @endforeach
        <p class="mt-5">{{ $posts->links() }}</p>
    </div>
    <!-- Vue -->
    <div id="app">
        <unit-list-component></unit-list-component>
        <router-view></router-view>
    </div>
</x-app-layout>
