<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

/**
 * Signing out has to work from a page that has been open too long.
 *
 * The CSRF token dies with the session, so any tab left open past
 * SESSION_LIFETIME carries a dead one. On most forms that is a nuisance. On
 * this one it is a security problem: the answer to "somebody else may be in
 * my account" is precisely the button that has stopped working.
 */
class SignOutAlwaysWorksTest extends TestCase
{
    use RefreshDatabase;

    protected function signedIn(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    /**
     * Laravel waives CSRF for its own test runner, which would make every
     * assertion here vacuous. Swap in a copy of the middleware that does not
     * know it is being tested, so the guard behaves as it does in a browser.
     */
    protected function withRealCsrf(): void
    {
        $replacement = fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };

        // The web group resolves the alias; bind both so it does not matter
        // which name a future Laravel puts in the group.
        $this->app->bind(ValidateCsrfToken::class, $replacement);
        $this->app->bind(VerifyCsrfToken::class, $replacement);
    }

    public function test_signing_out_with_a_stale_token_still_ends_the_session(): void
    {
        $this->withRealCsrf();
        $this->signedIn();

        // Same shape as a tab left open overnight: the form posts, and the
        // token belongs to a session that no longer exists.
        $this->post(route('logout'), ['_token' => 'a-token-from-a-session-that-has-gone'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_token_mismatch_anywhere_else_is_still_refused(): void
    {
        $this->withRealCsrf();
        $this->signedIn();

        // The leniency is for sign-out alone. Everything else keeps the guard.
        $this->assertThrows(
            fn () => $this->withoutExceptionHandling()
                ->put(route('account.profile.password'), [
                    '_token' => 'wrong',
                    'current_password' => 'x',
                    'password' => 'Whatever12',
                    'password_confirmation' => 'Whatever12',
                ]),
            TokenMismatchException::class
        );

        $this->assertAuthenticated();
    }

    public function test_typing_the_logout_url_offers_a_working_button(): void
    {
        $this->signedIn();

        // A GET used to be a bare 405. It now answers with a fresh token.
        $this->get('/logout')
            ->assertOk()
            ->assertSee('Sign out?', false);

        // Confirming still signs out, and the visitor is still signed in until
        // they do — a prefetched link must not end the session on its own.
        $this->assertAuthenticated();
    }

    public function test_the_logout_url_sends_a_visitor_who_is_not_signed_in_to_the_form(): void
    {
        $this->get('/logout')->assertRedirect(route('login'));
    }
}
