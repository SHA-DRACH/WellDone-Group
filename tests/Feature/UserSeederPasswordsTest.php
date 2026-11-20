<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\StaffAccountCreated;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The staff accounts the seeder creates for the leadership team.
 *
 * The development password is public — it is in the repository — so it must
 * never reach a live site; a live install emails each person a link to choose
 * their own; and re-running the seeder never resets a changed password.
 */
class UserSeederPasswordsTest extends TestCase
{
    use RefreshDatabase;

    private const ICT_DIRECTOR = 'shadrach.jimice@welldonegroupinc.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, DivisionSeeder::class]);
    }

    private function seedAsLiveInstall(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Called directly: `db:seed` asks for confirmation in production.
        $this->app->make(UserSeeder::class)->setContainer($this->app)->__invoke();
    }

    public function test_live_accounts_get_private_passwords_and_must_change_them(): void
    {
        Notification::fake();

        $this->seedAsLiveInstall();

        $user = User::query()->where('email', self::ICT_DIRECTOR)->firstOrFail();

        $this->assertFalse(Hash::check('WellDone@2026', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_live_install_emails_every_leader_a_set_password_link(): void
    {
        Notification::fake();

        $this->seedAsLiveInstall();

        $leaders = User::query()->get();

        $this->assertCount(6, $leaders);
        Notification::assertSentTo($leaders, StaffAccountCreated::class);

        // Re-running does not email anyone a second time.
        $this->seedAsLiveInstall();
        Notification::assertSentTimes(StaffAccountCreated::class, 6);
    }

    public function test_leaders_get_their_role_and_division(): void
    {
        $this->seed(UserSeeder::class);

        $ict = User::query()->where('email', self::ICT_DIRECTOR)->firstOrFail();
        $medical = User::query()->where('email', 'william.davis@welldonegroupinc.com')->firstOrFail();

        $this->assertTrue($ict->hasRole('Super Administrator'));
        $this->assertSame('ict-services', $ict->division->slug);
        $this->assertTrue($medical->hasRole('Division Manager'));
        $this->assertSame('medical-equipment', $medical->division->slug);
    }

    public function test_placeholder_accounts_are_removed(): void
    {
        User::factory()->create(['email' => 'superadmin@welldonegroup.com']);

        $this->seed(UserSeeder::class);

        $this->assertNull(User::query()->where('email', 'superadmin@welldonegroup.com')->first());
    }

    public function test_reseeding_keeps_a_changed_password(): void
    {
        $this->seed(UserSeeder::class);

        $user = User::query()->where('email', self::ICT_DIRECTOR)->firstOrFail();
        $user->forceFill(['password' => Hash::make('a-new-private-password')])->save();

        $this->seed(UserSeeder::class);

        $this->assertTrue(Hash::check('a-new-private-password', $user->fresh()->password));
    }
}
