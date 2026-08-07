<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Where an account whose password was set by HR chooses its own.
 *
 * The current password is still required: it proves the person at the
 * keyboard is the one HR handed the password to, and not somebody who found
 * an unattended session.
 */
class ForcedPasswordChangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request): RedirectResponse|View
    {
        if (! $request->user()->must_change_password) {
            return redirect()->to(Navigation::homeRouteFor($request->user()));
        }

        return view('auth.passwords.change');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'That is not the password you were given.',
        ]);

        // A "new" password identical to the old one would leave the shared
        // secret in place, which is the one thing this screen exists to end.
        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Please choose a password different from the one you were given.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->to(Navigation::homeRouteFor($user))
            ->with('status', 'Your password has been changed. Welcome.');
    }
}
