<x-app-layout>
    @if (count($users) > 0)
        <div class="container mx-auto">
            <div class="flex flex-wrap -mx-4">
                @foreach ($users as $user)
                    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 px-4 mb-8">
                        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                            <div class="p-4">
                                <h3 class="text-xl font-medium text-gray-800">{{ $user->name }}</h3>
                                <p class="text-gray-600">Email: {{ $user->email }}</p>
                                @if ($user->hasRole('admin'))
                                    <span
                                        class="inline-block bg-blue-500 text-white rounded-full px-3 py-1 text-sm font-semibold">管理者</span>
                                @else
                                    <span
                                        class="inline-block bg-gray-500 text-white rounded-full px-3 py-1 text-sm font-semibold">ユーザー</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p>ユーザーが見つかりません。</p>
    @endif
</x-app-layout>
