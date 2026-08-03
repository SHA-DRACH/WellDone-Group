<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Verifies a signed-in user's email address with a code, as an alternative
 * to the emailed link.
 *
 * A code is easier on a phone, where the link often opens in a browser with
 * no session and the verification silently fails.
 */
class OtpVerificationController extends Controller
{
    public const PURPOSE = 'email_verification';

    public function __construct(protected OtpService $otp)
    {
        $this->middleware('auth');
    }

    public function show(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->to(Navigation::homeRouteFor($request->user()));
        }

        return view('auth.otp.verify', ['email' => $request->user()->email]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->to(Navigation::homeRouteFor($user));
        }

        if (! $this->otp->canIssue($user->email, self::PURPOSE)) {
            return back()->withErrors([
                'code' => 'Several codes have already been sent. Please wait a few minutes before requesting another.',
            ]);
        }

        $this->otp->issue($user->email, self::PURPOSE, $user, $request->ip());

        return back()->with('status', 'We have sent a six-digit code to '.$user->email.'.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:'.OtpService::LENGTH],
        ], [
            'code.digits' => 'The code is six digits.',
        ]);

        $user = $request->user();

        $result = $this->otp->verify($user->email, self::PURPOSE, $request->input('code'));

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->to(Navigation::homeRouteFor($user))
            ->with('status', 'Your email address has been verified. Welcome.');
    }
}
