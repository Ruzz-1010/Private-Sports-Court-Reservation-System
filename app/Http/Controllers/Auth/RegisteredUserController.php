<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Auth\EmailVerificationController;
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must agree to the Terms and Conditions to create an account.',
            'first_name.required' => 'Enter your first name.',
            'last_name.required' => 'Enter your last name.',
        ]);

        // The form asks for the name in two parts, but the database keeps one
        // `name` column, so reports, receipts and emails need no change.
        $user = User::create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => Role::Customer,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // The account exists but stays closed until the emailed code is entered.
        // A mail problem must not leave the customer on a dead screen, so the
        // verification page offers a resend button, and the status message tells
        // them what to do next.
        return app(EmailVerificationController::class)->send($user, $request);
    }
}
