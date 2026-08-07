<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Remembers which account is waiting to confirm its address.
 *
 * Verification now happens *before* sign-in, so the code screen has no
 * authenticated user to work from. This holds the account in the session
 * between the refused sign-in and the confirmed code — a session key rather
 * than anything in the URL, so the address being verified is never guessable
 * or forgeable from outside the browser that started the attempt.
 *
 * Nothing here grants access. The pending account can only reach the code
 * screen; it becomes a signed-in session only once a valid code or a signed
 * link proves the address.
 */
class PendingVerification
{
    public const SESSION_KEY = 'auth.awaiting_verification';

    public static function remember(User $user): void
    {
        session()->put(self::SESSION_KEY, $user->getKey());
    }

    /**
     * The account waiting to verify, if there still is one.
     *
     * Self-cleaning: an account that has since been verified, deactivated or
     * deleted is dropped rather than returned, so a stale key cannot keep the
     * code screen alive for an account that has no business being there.
     */
    public static function user(): ?User
    {
        $id = session()->get(self::SESSION_KEY);

        if (blank($id)) {
            return null;
        }

        $user = User::query()->find($id);

        if (! $user || ! $user->is_active || $user->hasVerifiedEmail()) {
            self::forget();

            return null;
        }

        return $user;
    }

    /**
     * Whoever the verification screens should act on: the signed-in user when
     * there is one, otherwise the account waiting at the door.
     */
    public static function subject(Request $request): ?User
    {
        return $request->user() ?: self::user();
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
