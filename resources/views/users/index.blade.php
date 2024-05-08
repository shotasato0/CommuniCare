<x-app-layout>
    @if (count($users) > 0)
        <div class="container mx-auto">
            <div class="flex flex-col -mx-4"> <!-- flex-wrap を flex-col に変更し、flex-direction を column に設定 -->
                @foreach ($users as $user)
                    <div class="w-full px-4 my-2 flex items-center"> <!-- flexとitems-centerを追加して横並びと中央揃えを実現 -->
                        <a href="{{ route('users.show', ['user' => $user->id]) }}" class="block flex-grow">
                            <!-- flex-growを追加して、リンクカードが可能な限りの幅を取るように設定 -->
                            <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:bg-gray-100">
                                <div class="p-4">
                                    <h3 class="text-xl font-medium text-gray-800">{{ $user->name }}</h3>
                                    @if ($user->hasRole('admin'))
                                        <span
                                            class="inline-block bg-blue-500 text-white rounded-full px-3 py-1 mt-2 text-sm font-semibold">管理者</span>
                                    @else
                                        <span
                                            class="inline-block bg-gray-500 text-white rounded-full px-3 py-1 mt-2 text-sm font-semibold">ユーザー</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <!-- 削除ボタン -->
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                            onsubmit="return confirm('本当に削除しますか？');" class="ml-4"> <!-- ml-4を追加してマージンを設定 -->
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                削除
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p>ユーザーが見つかりません。</p>
    @endif
    <p class="mt-5">{{ $users->links() }}</p>
</x-app-layout>
