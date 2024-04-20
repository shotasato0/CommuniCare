<x-app-layout>
    <div class="container mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden my-4">
            <div class="p-4">
                <h2 class="text-2xl font-bold mb-2">{{ $user->name }}</h2>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                @if ($user->hasRole('admin'))
                    <span
                        class="inline-block bg-blue-500 text-white rounded-full px-3 py-1 text-sm font-semibold mt-3">管理者</span>
                @else
                    <span
                        class="inline-block bg-gray-500 text-white rounded-full px-3 py-1 text-sm font-semibold mt-3">ユーザー</span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
