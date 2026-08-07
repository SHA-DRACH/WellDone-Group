<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * laravel/ui points here after a reset; the /home route forwards each
     * user to the destination their role and division imply.
     */
    protected $redirectTo = '/home';

    /**
     * Finish the reset.
     *
     * Two things are added to the framework's behaviour, and both exist to
     * stop the reset leaving the account in a state it cannot get out of.
     *
     * `must_change_password` marks a password somebody else chose — HR hands
     * one over in person for staff with no reliable email. Choosing a new one
     * here is exactly what that flag is waiting for, so it has to be cleared.
     * Leaving it set locks the account out permanently: the change screen
     * asks for the old password, which they never knew, and refuses the new
     * one for matching the current password. There is no third answer.
     *
     * The address is treated as verified for the same reason it is on the
     * emailed-code path — the token only ever reached them through that
     * mailbox, so control of it is already proven.
     *
     * @param  \App\Models\User  $user
     * @param  string  $password
     */
    protected function resetPassword($user, $password): void
    {
        $this->setUserPassword($user, $password);

        $user->forceFill([
            'must_change_password' => false,
            'email_verified_at' => $user->email_verified_at ?? now(),
            // Signs every other session out; a reset is the usual response to
            // suspecting somebody else has the old password.
            'remember_token' => Str::random(60),
        ])->save();

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        $this->guard()->login($user);
    }

    /** Kept to satisfy the trait's type hint against the framework contract. */
    protected function credentials(\Illuminate\Http\Request $request): array
    {
        return $request->only('email', 'password', 'password_confirmation', 'token');
    }

    /**
     * Passwords set here must clear the same bar as everywhere else in the
     * application; the framework default is eight characters and nothing more.
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /** @param  CanResetPassword  $user */
    protected function setUserPassword($user, $password): void
    {
        $user->password = \Illuminate\Support\Facades\Hash::make($password);
    }
}
