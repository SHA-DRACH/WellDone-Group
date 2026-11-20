<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A division head is never shown another division — not in a filter, not in
 * a form's division picker — and the executive and system roles open the
 * panel group-wide.
 */
class DivisionVisibilityAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected Division $mine;

    protected Division $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->mine = Division::factory()->create(['name' => 'Medical Supply Services', 'slug' => 'medical', 'is_active' => true]);
        $this->theirs = Division::factory()->create(['name' => 'Mining Services', 'slug' => 'mining', 'is_active' => true]);
    }

    protected function userWith(string $role, ?Division $division = null): User
    {
        $user = User::factory()->create([
            'division_id' => $division?->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    /** Pages whose filters or forms list divisions. */
    public static function divisionPickers(): array
    {
        return [
            'projects list' => ['admin.projects.index'],
            'quotes list' => ['admin.quotes.index'],
            'services list' => ['admin.services.index'],
            'vacancies list' => ['admin.careers.index'],
            'galleries list' => ['admin.galleries.index'],
            'new project form' => ['admin.projects.create'],
            'new service form' => ['admin.services.create'],
            'new vacancy form' => ['admin.careers.create'],
            'new download form' => ['admin.downloads.create'],
        ];
    }

    /** @dataProvider divisionPickers */
    public function test_a_division_head_is_offered_only_their_own_division(string $route): void
    {
        $head = $this->userWith(Roles::DIVISION_MANAGER, $this->mine);

        $this->actingAs($head)
            ->get(route($route))
            ->assertOk()
            ->assertSee('Medical Supply Services')
            ->assertDontSee('Mining Services');
    }

    public function test_group_wide_staff_are_offered_every_division(): void
    {
        $admin = $this->userWith(Roles::SYSTEM_ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Medical Supply Services')
            ->assertSee('Mining Services');
    }

    /** The new roles reach the group dashboard and the pages their job needs. */
    public static function rolePages(): array
    {
        return [
            'System Administrator' => [Roles::SYSTEM_ADMIN, ['admin.dashboard', 'admin.settings.edit', 'admin.users.index', 'admin.projects.index', 'admin.blogs.index', 'admin.seo.index']],
            'Managing Director' => [Roles::MANAGING_DIRECTOR, ['admin.dashboard', 'admin.quotes.index', 'admin.projects.index', 'admin.clients.index']],
            'Strategy Director' => [Roles::STRATEGY_DIRECTOR, ['admin.dashboard', 'admin.quotes.index', 'admin.clients.index', 'admin.partners.index', 'admin.blogs.index']],
        ];
    }

    /** @dataProvider rolePages */
    public function test_new_roles_open_their_pages(string $role, array $routes): void
    {
        $user = $this->userWith($role);

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_only_the_super_administrator_edits_roles(): void
    {
        $this->actingAs($this->userWith(Roles::SYSTEM_ADMIN))
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_the_strategy_director_cannot_change_site_settings(): void
    {
        $this->actingAs($this->userWith(Roles::STRATEGY_DIRECTOR))
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }
}
