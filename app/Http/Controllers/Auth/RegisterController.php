<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ClientAccountCreated;
use App\Support\Roles;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Public self-registration.
 *
 * Anyone may create an account here, and every account created this way gets
 * the Client role — which carries no admin permissions at all, so a
 * registered visitor can never reach the control panel however the route is
 * approached. Staff accounts are created by an administrator instead.
 */
class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/account';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data): ValidatorContract
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:150'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
            // Honeypot — a real visitor never fills this in.
            'website_url' => ['prohibited'],
        ], [
            'terms.accepted' => 'Please accept the terms of use and privacy policy to continue.',
            'website_url.prohibited' => 'Your submission could not be processed.',
        ], [
            'terms' => 'terms of use',
        ]);
    }

    protected function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            // Reuse the staff `position` column to hold the organisation a
            // client says they represent; it is only ever shown back to them.
            'position' => $data['company'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $user->assignRole(Roles::CLIENT);

        return $user;
    }

    /**
     * Laravel's Registered event already triggers the verification email
     * (User implements MustVerifyEmail); this adds the welcome message.
     */
    protected function registered(\Illuminate\Http\Request $request, $user)
    {
        $user->notify(new ClientAccountCreated($user));

        return null;
    }
}
