<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Services\Auth\EmailVerificationCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

/**
 * Email verification with a six-digit code.
 *
 * The customer is signed in right after registering so they can see the code
 * screen, but every customer route is closed until the code is entered. A mail
 * failure is reported back on the screen instead of being swallowed, because a
 * code that never arrives leaves the account unusable.
 */
class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationCodes $codes) {}

    /**
     * The code entry screen.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.verify-email', [
            'email' => $user->email,
            'resendIn' => $this->codes->secondsUntilResend($user),
        ]);
    }

    /**
     * Check the submitted code and let the customer in.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{4,8}$/'],
        ], [
            'code.required' => 'Enter the code we emailed you.',
            'code.regex' => 'The code is made of digits only.',
        ]);

        if (! $this->codes->verify($user, $validated['code'])) {
            return back()->withErrors([
                'code' => 'That code is wrong or has expired. Request a new code and try again.',
            ]);
        }

        $user->markEmailAsVerified();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account.email_verified',
            'entity_type' => $user->getMorphClass(),
            'entity_id' => $user->id,
            'details' => ['email' => $user->email],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Your email is verified. Welcome to the club!');
    }

    /**
     * Send a new code, rate limited by the service.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        if (($wait = $this->codes->secondsUntilResend($user)) > 0) {
            return back()->withErrors([
                'code' => "Please wait {$wait} seconds before requesting another code.",
            ]);
        }

        return $this->send($user, $request);
    }

    /**
     * Issue a code and email it. Shared by registration and resend.
     *
     * @param  Request|null  $request  Null when called right after registration,
     *                                 where the caller reports the failure itself.
     */
    public function send(User $user, ?Request $request = null): RedirectResponse
    {
        $code = $this->codes->issue($user);

        try {
            $user->notify(new EmailVerificationCode($code));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('verification.notice')->withErrors([
                'code' => 'We could not send the email. Check your connection and press "Send a new code".',
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account.verification_code_sent',
            'entity_type' => $user->getMorphClass(),
            'entity_id' => $user->id,
            'details' => ['email' => $user->email],
            'ip_address' => $request?->ip(),
        ]);

        return redirect()->route('verification.notice')
            ->with('status', 'We sent a new code to '.$user->email.'.');
    }

    /**
     * Sign the customer out from the verification screen.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
