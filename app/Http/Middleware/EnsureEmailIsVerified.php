<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes every signed-in route until the email address is verified.
 *
 * Administrators are created by the seeder, which marks them verified, so this
 * only ever blocks a fresh self-registered customer.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your email address is not verified.'], 403)
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
