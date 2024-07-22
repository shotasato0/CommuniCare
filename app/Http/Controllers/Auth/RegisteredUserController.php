<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        Log::info('Displaying registration view');
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Registration request received', ['request' => $request->all()]);

        $request->validate([
            'name' => 'required|string|max:255',
            'username_id' => 'required|string|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Log::info('Validation passed');

        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
        ]);

        Log::info('User created', ['user' => $user]);

        event(new Registered($user));

        Log::info('Registered event dispatched');

        Auth::login($user);

        Log::info('User logged in', ['user' => $user]);

        return redirect()->route('dashboard');
    }
}
