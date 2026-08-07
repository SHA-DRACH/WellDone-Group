<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an account whose password was set for them on the change screen until
 * they choose their own.
 *
 * The whole point of the flag is that somebody else knows this password, so
 * the block has to apply everywhere rather than at the door of one module.
 * Four things stay reachable: the change screen itself, signing out,
 * verifying the address, and the one-time-code routes — none of them are
 * useful to an impostor, and locking someone out of sign-out would be
 * perverse.
 */
class EnsurePasswordIsChanged
{
    /** Routes that remain reachable while a change is outstanding. */
    protected array $allowed = [
        'password.change',
        'password.change.update',
        'logout',
        'logout.confirm',
        'verification.notice',
        'verification.verify',
        'verification.resend',
        'otp.verify',
        'otp.send',
        'otp.confirm',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs($this->allowed)) {
            return $next($request);
        }

        // An XHR cannot follow a redirect usefully; say so plainly instead.
        if ($request->expectsJson()) {
            abort(403, 'You must change your password before continuing.');
        }

        return redirect()->route('password.change');
    }
}
