<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Navigation;
use App\Support\PendingVerification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Confirms an address with a six-digit code, before sign-in.
 *
 * This is the screen a visitor lands on after registering, or after trying to
 * sign in with an address they never confirmed. It deliberately works without
 * a session: the code is the proof of ownership, so requiring someone to be
 * signed in before they may prove who they are is the wrong way round — and
 * on a phone the emailed link so often opens in a browser with no session
 * that the code is the path most people actually take.
 *
 * The account being confirmed comes from PendingVerification, held in the
 * session. A signed-in but unverified user (an employee whose login HR
 * created, say) reaches the same screen and is handled identically.
 */
class OtpVerificationController extends Controller
{
    public const PURPOSE = OtpService::PURPOSE_EMAIL_VERIFICATION;

    public function __construct(protected OtpService $otp) {}

    public function show(Request $request): RedirectResponse|View
    {
        $user = PendingVerification::subject($request);

        if (! $user) {
            return $this->nothingToVerify($request);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->alreadyVerified($request, $user);
        }

        return view('auth.otp.verify', [
            'email' => $user->email,
            'signedIn' => $request->user() !== null,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = PendingVerification::subject($request);

        if (! $user) {
            return $this->nothingToVerify($request);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->alreadyVerified($request, $user);
        }

        if (! $this->otp->canIssue($user->email, self::PURPOSE)) {
            return back()->withErrors([
                'code' => 'Several codes have already been sent. Please wait a few minutes before requesting another.',
            ]);
        }

        // The full verification notification, so the replacement email carries
        // the link as well as the code — the same two ways in as the first one.
        $user->sendEmailVerificationNotification();

        return back()->with('status', 'We have sent a new six-digit code to '.$user->email
            .'. Any earlier code has stopped working.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:'.OtpService::LENGTH],
        ], [
            'code.digits' => 'The code is six digits.',
        ]);

        $user = PendingVerification::subject($request);

        if (! $user) {
            return $this->nothingToVerify($request);
        }

        $result = $this->otp->verify($user->email, self::PURPOSE, $request->input('code'));

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $this->completeSignIn($request, $user);
    }

    /**
     * A confirmed address is the last thing standing between the visitor and
     * their account, so finish the job rather than asking them to type the
     * password they just typed. The code was emailed to that address and
     * proves control of it.
     */
    protected function completeSignIn(Request $request, User $user): RedirectResponse
    {
        PendingVerification::forget();

        if (! $request->user()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $user->recordLogin($request->ip());
        }

        return redirect()->to(Navigation::homeRouteFor($user))
            ->with('status', 'Your email address has been confirmed. Welcome.');
    }

    /** Somebody reached the screen with nothing waiting on it. */
    protected function nothingToVerify(Request $request): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'email' => 'Please sign in to continue. If your address is not yet confirmed, we will send you a code.',
        ]);
    }

    protected function alreadyVerified(Request $request, User $user): RedirectResponse
    {
        PendingVerification::forget();

        if (! $request->user()) {
            return redirect()->route('login')
                ->with('status', 'That address is already confirmed. Please sign in.');
        }

        return redirect()->to(Navigation::homeRouteFor($user));
    }
}
