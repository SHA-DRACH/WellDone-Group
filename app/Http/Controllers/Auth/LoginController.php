<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Navigation;
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

    public function __construct()
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
        $user->recordLogin($request->ip());

        if ($request->expectsJson()) {
            return response()->json(['redirect' => Navigation::homeRouteFor($user)]);
        }

        return redirect()->intended(Navigation::homeRouteFor($user));
    }
}
