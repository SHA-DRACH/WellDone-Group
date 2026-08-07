<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Password reset by emailed code, as an alternative to the reset link.
 *
 * The request step never reveals whether an address is registered — the same
 * message is returned either way, so this cannot be used to enumerate who
 * holds an account.
 */
class OtpPasswordResetController extends Controller
{
    public const PURPOSE = OtpService::PURPOSE_PASSWORD_RESET;

    public function __construct(protected OtpService $otp)
    {
        $this->middleware('guest');
    }

    public function request(): View
    {
        return view('auth.otp.request');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
        ]);

        $email = Str::lower($validated['email']);
        $user = User::query()->where('email', $email)->where('is_active', true)->first();

        /*
         * Deliberately identical response whether or not the account exists.
         * Anything else turns this form into a way of discovering which
         * addresses are registered.
         */
        if ($user && $this->otp->canIssue($email, self::PURPOSE)) {
            $this->otp->issue($email, self::PURPOSE, $user, $request->ip());
        }

        return redirect()
            ->route('password.otp.verify', ['email' => $email])
            ->with('status', 'If that address has an account, a six-digit code is on its way to it.');
    }

    public function verify(Request $request): View
    {
        return view('auth.otp.reset', [
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'code' => ['required', 'string', 'digits:'.OtpService::LENGTH],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'code.digits' => 'The code is six digits.',
        ]);

        $email = Str::lower($validated['email']);

        $result = $this->otp->verify($email, self::PURPOSE, $validated['code']);

        if (! $result['ok']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => $result['message']]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'We could not complete the reset. Please start again.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            // A correct code proves control of the mailbox, so treat the
            // address as verified if it was not already.
            'email_verified_at' => $user->email_verified_at ?? now(),
            // Choosing their own password is precisely what the flag waits
            // for. Left set, it traps the account: the change screen wants
            // the password HR handed over, which they never knew, and rejects
            // the new one for matching the current password.
            'must_change_password' => false,
            // Force other sessions to sign in again with the new password.
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been changed. Please sign in with it.');
    }
}
