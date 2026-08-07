<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Navigation;
use App\Support\PendingVerification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The emailed link, as opposed to the code.
 *
 * Laravel's stock VerifiesEmails trait puts this route behind `auth`, which
 * assumes the link is opened in the same signed-in browser that asked for it.
 * That assumption fails constantly: mail is read on a phone, the link opens in
 * a browser with no session, and the visitor is bounced to a sign-in form
 * instead of being verified. Since verification is now a prerequisite for
 * signing in, that would be a closed loop.
 *
 * So the link stands on its own. It is safe to: the URL is signed with the
 * application key and expires, and the hash in it is derived from the address
 * being confirmed, so neither the account nor the address can be swapped for
 * another without invalidating the signature.
 *
 * Confirming does not sign anyone in. The link only proves the address is
 * reachable — it can be forwarded, and it sits in an inbox for as long as the
 * inbox lives — so it is not treated as a credential. It marks the address
 * confirmed and sends the visitor to sign in with their password.
 */
class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /** Where the `verified` middleware sends anyone who has not confirmed yet. */
    public function show(Request $request): RedirectResponse
    {
        $user = PendingVerification::subject($request);

        if ($user && $user->hasVerifiedEmail()) {
            return redirect()->to(Navigation::homeRouteFor($user));
        }

        return redirect()->route('otp.verify');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);

        // A signature covers the URL, not the state behind it: the account can
        // still have been removed or the address changed since it was sent.
        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->withErrors([
                'email' => 'That verification link is no longer valid. Sign in to have a new one sent.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Please contact your administrator.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->done($request, $user, 'That address was already confirmed. Please sign in.');
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return $this->done($request, $user, 'Thank you — your email address is confirmed. You can sign in now.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = PendingVerification::subject($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->done($request, $user, 'That address is already confirmed. Please sign in.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'We have sent a new email to '.$user->email
            .', with a fresh code and link. Any earlier code has stopped working.');
    }

    /**
     * A visitor who was already signed in — an employee working through a
     * forced verification, say — carries on where they were. Everyone else
     * goes to the sign-in form.
     */
    protected function done(Request $request, User $user, string $message): RedirectResponse
    {
        PendingVerification::forget();

        if ($request->user()) {
            return redirect()->to(Navigation::homeRouteFor($user))
                ->with('status', 'Your email address has been confirmed. Welcome.');
        }

        return redirect()->route('login')->with('status', $message);
    }
}
