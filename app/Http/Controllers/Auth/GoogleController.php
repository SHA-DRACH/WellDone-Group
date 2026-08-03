<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ClientAccountCreated;
use App\Support\Navigation;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * "Continue with Google" for public visitors.
 *
 * Accounts created this way get the Client role, exactly like the ordinary
 * registration form — signing in with Google never grants staff access.
 *
 * An existing staff member may still use it to sign in, provided Google
 * confirms they own the address on the account.
 */
class GoogleController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->only(['redirect', 'callback']);
    }

    public function redirect(): RedirectResponse
    {
        abort_unless($this->configured(), 404, 'Google sign-in is not configured.');

        return Socialite::driver('google')
            // `select_account` stops Google silently reusing a session, which
            // matters on shared machines.
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->configured(), 404);

        // The user declined consent, or Google returned an error.
        if ($request->missing('code') || $request->filled('error')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in was cancelled. You can sign in with your email and password instead.']);
        }

        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google sign-in failed', ['message' => $e->getMessage()]);

            return redirect()->route('login')
                ->withErrors(['email' => 'We could not complete Google sign-in. Please try again, or use your email and password.']);
        }

        $email = Str::lower((string) $googleUser->getEmail());

        if (blank($email)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google did not share an email address with us, so we cannot sign you in that way.']);
        }

        [$user, $wasCreated] = $this->resolveUser($googleUser, $email);

        if (! $user->is_active) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been deactivated. Please contact your administrator.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $user->recordLogin($request->ip());

        if ($wasCreated) {
            $user->notify(new ClientAccountCreated($user));

            return redirect()->to(Navigation::homeRouteFor($user))
                ->with('status', 'Welcome — your account has been created with Google.');
        }

        return redirect()->intended(Navigation::homeRouteFor($user));
    }

    /**
     * Find the matching account or create one.
     *
     * @return array{0: User, 1: bool} the user, and whether it was just created
     */
    protected function resolveUser(SocialiteUser $googleUser, string $email): array
    {
        return DB::transaction(function () use ($googleUser, $email) {
            // Match on the provider id first — it survives an email change.
            $user = User::query()
                ->withTrashed()
                ->where('provider', 'google')
                ->where('provider_id', $googleUser->getId())
                ->first();

            if ($user) {
                $this->syncProfile($user, $googleUser);

                return [$user, false];
            }

            // Otherwise link to an existing account with the same address.
            // Safe because Google has verified ownership of that address.
            $user = User::query()->withTrashed()->where('email', $email)->first();

            if ($user) {
                $user->forceFill([
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'provider_avatar' => $googleUser->getAvatar(),
                    // Google has confirmed the address, so treat it as verified.
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return [$user, false];
            }

            $user = new User;

            $user->forceFill([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                // No usable password: this account signs in through Google.
                // "Forgotten password" still works if they want to set one.
                'password' => Str::random(48),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'provider_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();

            $user->assignRole(Roles::CLIENT);

            return [$user, true];
        });
    }

    protected function syncProfile(User $user, SocialiteUser $googleUser): void
    {
        $user->forceFill([
            'provider_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->saveQuietly();
    }

    /** True once the Google credentials are present in .env. */
    public static function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
