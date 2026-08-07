<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * A password reset has to clear the "somebody else chose this" flag.
 *
 * Without that, an account whose first password was set by HR and who then
 * uses "Forgotten?" is locked out for good: the reset succeeds, but the
 * forced-change screen still demands the old password they never knew — and
 * refuses the new one for being the same as the current one. There is no
 * third answer, so the account is unreachable.
 */
class PasswordResetClearsForcedChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function handedOverAccount(): User
    {
        return User::factory()->create([
            'password' => 'HandedOver1',
            'must_change_password' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_the_reset_link_clears_the_forced_change_flag(): void
    {
        $user = $this->handedOverAccount();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ChosenByThem9',
            'password_confirmation' => 'ChosenByThem9',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_the_emailed_code_clears_the_forced_change_flag(): void
    {
        $user = $this->handedOverAccount();

        ['code' => $code] = app(OtpService::class)->mint(
            $user->email, OtpService::PURPOSE_PASSWORD_RESET, $user, '127.0.0.1'
        );

        $this->post(route('password.otp.update'), [
            'email' => $user->email,
            'code' => $code,
            'password' => 'ChosenByThem9',
            'password_confirmation' => 'ChosenByThem9',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    /**
     * The deadlock itself, stated as a test: after resetting, the account must
     * be able to reach an ordinary page rather than being bounced back to the
     * change screen for ever.
     */
    public function test_the_account_is_not_trapped_on_the_change_screen_after_a_reset(): void
    {
        $user = $this->handedOverAccount();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ChosenByThem9',
            'password_confirmation' => 'ChosenByThem9',
        ]);

        $this->actingAs($user->fresh())
            ->get('/account')
            ->assertOk();
    }
}
