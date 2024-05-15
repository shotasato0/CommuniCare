<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" id="registration-form">
        @csrf

        <!-- Admin Checkbox -->
        <div>
            <x-input-label for="is_admin" :value="__('Register as administrator?')" />
            <input type="checkbox" id="is_admin" name="is_admin"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <!-- Tenant Name -->
        <div id="nursing_home_container" style="display: none;" class="mt-4">
            <x-input-label for="tenant_name" :value="__('介護施設名')" />
            <x-text-input id="tenant_name" class="block mt-1 w-full" type="text" name="tenant_name" :value="old('tenant_name')"
                autofocus autocomplete="tenant_name" />
            <x-input-error :messages="$errors->get('tenant_name')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')"
                required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Id --}}
        <div class="mt-4">
            <x-input-label for="username_id" :value="__('Username ID')" />
            <x-text-input id="username_id" class="block mt-1 w-full" type="text" name="username_id" :value="old('username_id')"
                required autofocus autocomplete="username_id" />
            <x-input-error :messages="$errors->get('username_id')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
