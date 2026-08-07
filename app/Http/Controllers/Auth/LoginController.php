<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Navigation;
use App\Support\PendingVerification;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * One sign-in form for the whole organisation.
 *
 * Where a user lands afterwards is decided by App\Support\Navigation:
 * group-level staff get the full control panel, division staff go to their
 * own division dashboard.
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct(protected OtpService $otp)
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Sign-in attempts are throttled per email + IP by the trait. This adds a
     * clear refusal for accounts that have been deactivated, rather than the
     * generic "credentials do not match".
     */
    protected function credentials(Request $request): array
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true]
        );
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $exists = User::query()
            ->where('email', $request->input($this->username()))
            ->where('is_active', false)
            ->exists();

        throw ValidationException::withMessages([
            $this->username() => $exists
                ? 'This account has been deactivated. Please contact your administrator.'
                : trans('auth.failed'),
        ]);
    }

    protected function authenticated(Request $request, $user): RedirectResponse|JsonResponse
    {
        if (! $user->hasVerifiedEmail()) {
            return $this->haltForVerification($request, $user);
        }

        $user->recordLogin($request->ip());

        if ($request->expectsJson()) {
            return response()->json(['redirect' => Navigation::homeRouteFor($user)]);
        }

        return redirect()->intended(Navigation::homeRouteFor($user));
    }

    /**
     * Turn an unverified account away at the door.
     *
     * The credentials were correct, so this is not a failure to report as one —
     * it is an unfinished registration. The session is dropped and the account
     * is parked as pending instead, which leaves it able to reach the code
     * screen and nothing else.
     *
     * A fresh code is sent on the way, because the one from registration has
     * almost certainly expired by the time somebody comes back to sign in.
     */
    protected function haltForVerification(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->guard()->logout();

        PendingVerification::remember($user);

        $status = 'Please confirm your email address before signing in. '
            .'We have sent a six-digit code to '.$user->email.'.';

        // Silently skip the code when the address has already had its fill for
        // now; the screen still lets them ask again once the window clears.
        if ($this->otp->canIssue($user->email, OtpService::PURPOSE_EMAIL_VERIFICATION)) {
            $user->sendEmailVerificationNotification();
        } else {
            $status = 'Please confirm your email address before signing in. '
                .'Use the most recent code we sent to '.$user->email.'.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $status,
                'redirect' => route('otp.verify'),
            ], 409);
        }

        return redirect()->route('otp.verify')->with('status', $status);
    }
}
