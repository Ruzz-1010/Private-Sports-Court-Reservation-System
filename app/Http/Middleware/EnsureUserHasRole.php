<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to one or more roles.
 *
 * Usage: ->middleware('role:admin') or ->middleware('role:admin,customer')
 * Enforced server-side; hiding a button is not authorization.
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || $user->role === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        abort_unless(in_array($user->role->value, $roles, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
