<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NursingHome;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username_id' => ['required', 'string', 'max:255', 'unique:users,username_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
        
        // Check if the registration is for an administrator and require nursing home name
        if ($request->boolean('is_admin')) {  // Change to boolean check for clarity
            $rules['nursing_home_name'] = ['required', 'string', 'max:255', 'unique:nursing_homes,name'];
        }

        $request->validate($rules);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
        ]);

        // If registering as an admin, create the nursing home record and associate it
        if ($request->boolean('is_admin')) {
            $nursingHome = NursingHome::create([
                'name' => $request->nursing_home_name,
            ]);

            // Associate the user with the newly created nursing home
            $user->nursing_home_id = $nursingHome->id;
            $user->save();

            // Find the admin role and assign it to the user
            $adminRole = Role::findByName('admin');
            $user->assignRole($adminRole);
        }

        Auth::login($user); // Log in the newly created user

        return redirect(route('dashboard'))->with('success', '新しいユーザーが正常に登録されました。');
    }
}
