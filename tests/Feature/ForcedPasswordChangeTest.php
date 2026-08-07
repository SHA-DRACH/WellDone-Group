<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The guarantee this suite protects: a password somebody else chose cannot be
 * used for anything except replacing itself.
 */
class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function flaggedUser(string $password = 'HandedOver1'): User
    {
        return User::factory()->create([
            'password' => $password,
            'must_change_password' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_a_flagged_account_is_redirected_away_from_other_pages(): void
    {
        $this->actingAs($this->flaggedUser())
            ->get('/portal')
            ->assertRedirect(route('password.change'));
    }

    public function test_a_flagged_account_may_still_reach_the_change_screen(): void
    {
        $this->actingAs($this->flaggedUser())
            ->get(route('password.change'))
            ->assertOk();
    }

    public function test_a_flagged_account_may_still_sign_out(): void
    {
        $this->actingAs($this->flaggedUser())
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_changing_the_password_clears_the_flag(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'HandedOver1',
                'password' => 'ChosenByMe9',
                'password_confirmation' => 'ChosenByMe9',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('ChosenByMe9', $user->password));
    }

    public function test_the_new_password_may_not_be_the_one_that_was_handed_over(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'HandedOver1',
                'password' => 'HandedOver1',
                'password_confirmation' => 'HandedOver1',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_the_wrong_current_password_is_rejected(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'NotThePassword1',
                'password' => 'ChosenByMe9',
                'password_confirmation' => 'ChosenByMe9',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_an_unflagged_account_is_not_held_anywhere(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertRedirect();
    }
}
