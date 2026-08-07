<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rendering a page must not leave an output buffer open behind it. */
class DivisionDashboardBufferTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_division_dashboard_closes_its_buffers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $division = Division::factory()->create(['is_active' => true]);
        $head = User::factory()->create([
            'division_id' => $division->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $head->assignRole(Roles::DIVISION_MANAGER);

        $before = ob_get_level();

        $this->actingAs($head)
            ->get(route('admin.division.dashboard', $division->slug))
            ->assertOk();

        $this->assertSame($before, ob_get_level(),
            'the page left '.(ob_get_level() - $before).' output buffer(s) open');
    }
}
