<x-app-layout>
    @if (count($users) > 0)
        <div class="container mx-auto">
            <div class="flex flex-col -mx-4"> <!-- flex-wrap を flex-col に変更し、flex-direction を column に設定 -->
                @foreach ($users as $user)
                    <div class="w-full px-4 my-2"> <!-- 横幅クラスを w-full に統一 -->
                        <!-- リンクをカード全体に適用 -->
                        <a href="{{ route('users.show', ['user' => $user->id]) }}" class="block">
                            <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:bg-gray-100">
                                <div class="p-4">
                                    <h3 class="text-xl font-medium text-gray-800">{{ $user->name }}</h3>
                                    {{-- <p class="text-gray-600">Email: {{ $user->email }}</p> --}}
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
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p>ユーザーが見つかりません。</p>
    @endif
    <p class="mt-5">{{ $users->links() }}</p>
</x-app-layout>
