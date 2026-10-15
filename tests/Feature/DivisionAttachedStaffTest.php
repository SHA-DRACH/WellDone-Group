<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Someone attached to a division works inside that division.
 *
 * Two rules, kept apart on purpose:
 *  - What they can open is whatever an administrator has granted through
 *    their roles and permissions — nothing is handed out automatically.
 *  - Whatever they can open shows their own division only. Attaching the
 *    account is what confines it, whatever the role.
 */
class DivisionAttachedStaffTest extends TestCase
{
    use RefreshDatabase;

    protected Division $ict;

    protected Division $mining;

    protected User $ictManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->ict = Division::factory()->create(['name' => 'ICT Services', 'slug' => 'ict-services', 'is_active' => true]);
        $this->mining = Division::factory()->create(['name' => 'Mining & Quarry', 'slug' => 'mining-quarry', 'is_active' => true]);

        $this->ictManager = $this->user(Roles::ICT_MANAGER, $this->ict, 'Shadrach ICT');
    }

    // ------------------------------------------------------------ confinement

    public function test_they_land_on_their_own_division_not_the_group_dashboard(): void
    {
        $this->actingAs($this->ictManager)
            ->get('/home')
            ->assertRedirect(route('admin.division.dashboard', $this->ict->slug));

        $this->actingAs($this->ictManager)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.division.dashboard', $this->ict->slug));
    }

    public function test_another_divisions_dashboard_is_refused(): void
    {
        $this->actingAs($this->ictManager)
            ->get(route('admin.division.dashboard', $this->mining->slug))
            ->assertForbidden();
    }

    public function test_the_sidebar_lists_only_their_own_division(): void
    {
        $this->actingAs($this->ictManager)
            ->get(route('admin.division.dashboard', $this->ict->slug))
            ->assertOk()
            ->assertSee('My Division')
            ->assertDontSee('Group Dashboard')
            ->assertDontSee(route('admin.division.dashboard', $this->mining->slug));
    }

    // ------------------------------------------------------------ nothing automatic

    public function test_division_tools_stay_closed_until_an_administrator_grants_them(): void
    {
        foreach (['admin.quotes.index', 'admin.projects.index', 'admin.careers.index'] as $route) {
            $this->actingAs($this->ictManager)->get(route($route))->assertForbidden();
        }

        $this->actingAs($this->ictManager)
            ->get(route('admin.division.records.index', $this->ict->slug))
            ->assertForbidden();

        $this->actingAs($this->ictManager)
            ->get(route('admin.division.dashboard', $this->ict->slug))
            ->assertOk()
            ->assertDontSee('Quote Requests')
            ->assertDontSee('Service &amp; Support Log', false);
    }

    public function test_once_granted_the_division_tools_open_for_their_division_only(): void
    {
        $this->ictManager->assignRole(Roles::DIVISION_MANAGER);

        foreach (['admin.quotes.index', 'admin.projects.index', 'admin.careers.index'] as $route) {
            $this->actingAs($this->ictManager)->get(route($route))->assertOk();
        }

        $this->actingAs($this->ictManager)
            ->get(route('admin.division.dashboard', $this->ict->slug))
            ->assertOk()
            ->assertSee('Quote Requests')
            ->assertSee('Service &amp; Support Log', false);

        $this->actingAs($this->ictManager)
            ->get(route('admin.division.records.index', $this->mining->slug))
            ->assertForbidden();
    }

    public function test_a_single_permission_can_be_granted_on_its_own(): void
    {
        $this->ictManager->givePermissionTo('projects.view');

        $this->actingAs($this->ictManager)->get(route('admin.projects.index'))->assertOk();
        $this->actingAs($this->ictManager)->get(route('admin.quotes.index'))->assertForbidden();
    }

    // ------------------------------------------------------------ granted, but still confined

    public function test_account_management_they_hold_reaches_only_their_own_people(): void
    {
        $this->user(Roles::DIVISION_OFFICER, $this->ict, 'Ivy ICT Officer');
        $miningHead = $this->user(Roles::DIVISION_MANAGER, $this->mining, 'Moses Mining Head');

        $this->actingAs($this->ictManager)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Ivy ICT Officer')
            ->assertDontSee('Moses Mining Head');

        $this->actingAs($this->ictManager)->get(route('admin.users.edit', $miningHead))->assertForbidden();

        $this->actingAs($this->ictManager)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertDontSee('value="'.Roles::ADMIN.'"', false);

        $this->assertFalse($this->ictManager->can('assignRoles', $this->ictManager));
    }

    public function test_the_divisions_list_shows_only_their_own_division(): void
    {
        $this->ictManager->givePermissionTo('divisions.view');

        $this->actingAs($this->ictManager)
            ->get(route('admin.divisions.index'))
            ->assertOk()
            ->assertSee('ICT Services')
            ->assertDontSee('Mining &amp; Quarry', false);

        $this->actingAs($this->ictManager)
            ->get(route('admin.divisions.edit', $this->mining->slug))
            ->assertForbidden();
    }

    // ------------------------------------------------------------ the same role elsewhere

    public function test_the_same_role_without_a_division_stays_company_wide(): void
    {
        $central = $this->user(Roles::ICT_MANAGER, null, 'Emmanuel Central');

        $this->actingAs($central)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($central)
            ->get(route('admin.division.dashboard', $this->mining->slug))
            ->assertOk();
    }

    public function test_an_administrator_with_a_division_still_sees_everything(): void
    {
        $admin = $this->user(Roles::ADMIN, $this->ict, 'Ada Admin');

        $this->actingAs($admin)->get(route('admin.division.dashboard', $this->mining->slug))->assertOk();
    }

    protected function user(string $role, ?Division $division, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'division_id' => $division?->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
