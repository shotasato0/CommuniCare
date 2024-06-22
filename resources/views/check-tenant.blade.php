<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tenant Information') }}
        </h2>
    </x-slot>

    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-4">Tenant Information</h1>
        <table class="table-auto w-full">
            <tr>
                <th class="px-4 py-2">Tenant ID</th>
                <td class="px-4 py-2">{{ $tenant->id }}</td>
            </tr>
            <tr>
                <th class="px-4 py-2">Tenant Name</th>
                <td class="px-4 py-2">{{ $tenant->name }}</td>
            </tr>
            <tr>
                <th class="px-4 py-2">Domain</th>
                <td class="px-4 py-2">{{ $tenant->domain }}</td>
            </tr>
            <tr>
                <th class="px-4 py-2">Created At</th>
                <td class="px-4 py-2">{{ $tenant->created_at }}</td>
            </tr>
        </table>
    </div>
</x-app-layout>
