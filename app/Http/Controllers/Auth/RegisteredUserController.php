<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Customer self-registration.
 *
 * The role is always forced to Customer here. An administrator account is only
 * ever created through AdminUserSeeder, never through a publicly selectable
 * registration field.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @param  array<string, string>  $request
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must agree to the Terms and Conditions to create an account.',
        ]);

        $user = User::create([
            ...$validated,
            'role' => Role::Customer,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }
}
