<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailWithCode;
use App\Services\OtpService;
use App\Support\PendingVerification;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The guarantee this suite protects: an address nobody has confirmed cannot
 * become a signed-in session, and confirming it takes either the code or the
 * link — neither of which requires being signed in first.
 */
class EmailVerificationBeforeLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate(Roles::CLIENT, 'web');
    }

    protected function unverified(string $password = 'Password123'): User
    {
        return User::factory()->create([
            'email' => 'visitor@example.com',
            'password' => $password,
            'email_verified_at' => null,
            'is_active' => true,
        ]);
    }

    /** The code the app just emailed, which only exists in plain text in transit. */
    protected function codeFor(User $user): string
    {
        $minted = app(OtpService::class)->mint(
            $user->email,
            OtpService::PURPOSE_EMAIL_VERIFICATION,
            $user,
        );

        return $minted['code'];
    }

    // ----------------------------------------------------------- The block

    public function test_correct_credentials_do_not_sign_in_an_unconfirmed_account(): void
    {
        $user = $this->unverified();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('otp.verify'));
        $this->assertGuest();
    }

    public function test_the_blocked_attempt_parks_the_account_and_sends_a_code(): void
    {
        $user = $this->unverified();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123']);

        $this->assertSame($user->id, session(PendingVerification::SESSION_KEY));
        Notification::assertSentTo($user, VerifyEmailWithCode::class);
    }

    public function test_a_confirmed_account_signs_in_as_before(): void
    {
        $user = User::factory()->create([
            'email' => 'confirmed@example.com',
            'password' => 'Password123',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_still_just_a_wrong_password(): void
    {
        $this->unverified();

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'visitor@example.com', 'password' => 'nope'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull(session(PendingVerification::SESSION_KEY));
    }

    // ----------------------------------------------------------- The code

    public function test_the_code_screen_is_reachable_without_signing_in(): void
    {
        $user = $this->unverified();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123']);

        $this->get(route('otp.verify'))
            ->assertOk()
            ->assertSee('Six-digit code')
            ->assertSee($user->email);
    }

    public function test_a_valid_code_confirms_the_address_and_signs_the_visitor_in(): void
    {
        $user = $this->unverified();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123']);

        $code = $this->codeFor($user);

        $this->post(route('otp.confirm'), ['code' => $code])->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNull(session(PendingVerification::SESSION_KEY));
    }

    public function test_a_wrong_code_confirms_nothing(): void
    {
        $user = $this->unverified();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123']);
        $this->codeFor($user);

        $this->from(route('otp.verify'))
            ->post(route('otp.confirm'), ['code' => '000000'])
            ->assertRedirect(route('otp.verify'))
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
    }

    public function test_the_code_screen_turns_away_a_visitor_with_nothing_pending(): void
    {
        $this->get(route('otp.verify'))->assertRedirect(route('login'));
    }

    // ----------------------------------------------------------- The link

    public function test_the_emailed_link_confirms_the_address_without_a_session(): void
    {
        $user = $this->unverified();

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($link)->assertRedirect(route('login'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_link_does_not_sign_anyone_in(): void
    {
        $user = $this->unverified();

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($link);

        // An emailed link can be forwarded and lingers in an inbox; it proves
        // the address is reachable, which is not the same as being its owner.
        $this->assertGuest();
    }

    public function test_a_tampered_link_confirms_nothing(): void
    {
        $user = $this->unverified();
        $other = User::factory()->create(['email' => 'someone.else@example.com', 'email_verified_at' => null]);

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        // Swap the account the signed URL points at.
        $this->get(str_replace('/'.$user->id.'/', '/'.$other->id.'/', $link))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_an_unsigned_link_is_refused(): void
    {
        $user = $this->unverified();

        $this->get(route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    // ------------------------------------------------- Already signed in

    /**
     * A staff login HR created, or a session that predates this rule. The same
     * screen serves them, acting on the signed-in user rather than a pending
     * one, and it offers sign-out instead of "back to sign in".
     */
    public function test_a_signed_in_but_unconfirmed_account_uses_the_same_screen(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)
            ->get(route('otp.verify'))
            ->assertOk()
            ->assertSee('Six-digit code')
            ->assertSee('Sign out');

        $this->actingAs($user)
            ->post(route('otp.confirm'), ['code' => $this->codeFor($user)])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_the_verified_middleware_sends_people_to_the_code_screen(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('otp.verify'));
    }

    // ----------------------------------------------------------- Registration

    public function test_registering_does_not_sign_the_new_account_in(): void
    {
        $this->post(route('register'), [
            'name' => 'New Visitor',
            'email' => 'new.visitor@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
        ])->assertRedirect(route('otp.verify'));

        $this->assertGuest();

        $user = User::query()->where('email', 'new.visitor@example.com')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertSame($user->id, session(PendingVerification::SESSION_KEY));
    }

    public function test_a_new_registration_can_confirm_and_get_straight_in(): void
    {
        $this->post(route('register'), [
            'name' => 'New Visitor',
            'email' => 'new.visitor@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
        ]);

        $user = User::query()->where('email', 'new.visitor@example.com')->firstOrFail();

        $this->post(route('otp.confirm'), ['code' => $this->codeFor($user)])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
