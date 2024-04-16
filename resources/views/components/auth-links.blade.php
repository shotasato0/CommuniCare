@if (Route::has('login'))
    <nav class="flex justify-center">
        @auth
            <a href="{{ url('/dashboard') }}"
                class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
                ダッシュボード
            </a>
        @else
            <a href="{{ route('login') }}"
                class="inline-flex text-white bg-indigo-500 border-0 py-2 px-6 my-8 focus:outline-none hover:bg-indigo-600 rounded text-lg">
                ログイン
            </a>
            {{-- @if (Route::has('register'))
                <a href="{{ route('register') }}"
                    class="ml-4 inline-flex text-gray-700 bg-gray-100 border-0 py-2 px-6 focus:outline-none hover:bg-gray-200 rounded text-lg">
                    新規登録
                </a>
            @endif --}}
        @endauth
    </nav>
@endif
