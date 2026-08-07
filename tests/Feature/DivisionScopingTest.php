<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\ContactMessage;
use App\Models\Division;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A division head runs one division and sees one division.
 *
 * The rule is enforced on the query and in the policy, never in the view, so
 * these tests go at it the way an actual attempt would: by asking for the
 * other division's URL directly rather than by looking for a missing link.
 */
class DivisionScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Division $mine;

    protected Division $theirs;

    protected User $head;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->mine = Division::factory()->create(['name' => 'Mining & Quarry', 'slug' => 'mining', 'is_active' => true]);
        $this->theirs = Division::factory()->create(['name' => 'ICT Services', 'slug' => 'ict', 'is_active' => true]);

        $this->head = User::factory()->create([
            'division_id' => $this->mine->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->head->assignRole(Roles::DIVISION_MANAGER);
    }

    // ------------------------------------------------------------ listings

    public function test_the_project_list_holds_only_their_own_division(): void
    {
        $ours = Project::factory()->create(['division_id' => $this->mine->id, 'title' => 'Bomi Quarry Expansion']);
        $theirs = Project::factory()->create(['division_id' => $this->theirs->id, 'title' => 'Ministry Network Rollout']);

        $this->actingAs($this->head)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee($ours->title)
            ->assertDontSee($theirs->title);
    }

    public function test_the_quotation_queue_holds_only_their_own_division(): void
    {
        $ours = QuoteRequest::factory()->create(['division_id' => $this->mine->id]);
        $theirs = QuoteRequest::factory()->create(['division_id' => $this->theirs->id]);

        $this->actingAs($this->head)
            ->get(route('admin.quotes.index'))
            ->assertOk()
            ->assertSee($ours->reference)
            ->assertDontSee($theirs->reference);
    }

    // ------------------------------------------------- direct URL attempts

    public function test_opening_another_divisions_project_is_refused(): void
    {
        $theirs = Project::factory()->create(['division_id' => $this->theirs->id]);

        $this->actingAs($this->head)
            ->get(route('admin.projects.edit', $theirs))
            ->assertForbidden();
    }

    public function test_updating_another_divisions_project_is_refused(): void
    {
        $theirs = Project::factory()->create(['division_id' => $this->theirs->id, 'title' => 'Untouched']);

        $this->actingAs($this->head)
            ->put(route('admin.projects.update', $theirs), [
                'title' => 'Renamed by the wrong division',
                'division_id' => $this->theirs->id,
                'status' => 'ongoing',
            ])
            ->assertForbidden();

        $this->assertSame('Untouched', $theirs->fresh()->title);
    }

    public function test_deleting_another_divisions_project_is_refused(): void
    {
        $theirs = Project::factory()->create(['division_id' => $this->theirs->id]);

        $this->actingAs($this->head)
            ->delete(route('admin.projects.destroy', $theirs))
            ->assertForbidden();

        $this->assertNotNull($theirs->fresh());
    }

    public function test_opening_another_divisions_dashboard_is_refused(): void
    {
        $this->actingAs($this->head)
            ->get(route('admin.division.dashboard', $this->theirs->slug))
            ->assertForbidden();
    }

    public function test_their_own_dashboard_opens(): void
    {
        $this->actingAs($this->head)
            ->get(route('admin.division.dashboard', $this->mine->slug))
            ->assertOk();
    }

    public function test_another_divisions_enquiry_is_refused(): void
    {
        $theirs = ContactMessage::factory()->create(['division_id' => $this->theirs->id]);

        $this->actingAs($this->head)
            ->get(route('admin.messages.show', $theirs))
            ->assertForbidden();
    }

    // ------------------------------------------------------ the group view

    public function test_the_group_dashboard_redirects_to_their_own(): void
    {
        // It counts the whole company. Being sent home beats being shown it.
        $this->actingAs($this->head)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.division.dashboard', $this->mine->slug));
    }

    // -------------------------------------------------------- forged input

    public function test_a_record_cannot_be_filed_under_another_division(): void
    {
        /*
         * The form only offers their own division, but the form is not the
         * boundary. A hand-made POST naming another division must still land
         * in theirs.
         */
        $this->actingAs($this->head)
            ->post(route('admin.services.store'), [
                'name' => 'Smuggled Service',
                'division_id' => $this->theirs->id,
                'icon' => 'fa-solid fa-gear',
                'summary' => 'Filed under the wrong division on purpose.',
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $service = Service::query()->where('name', 'Smuggled Service')->first();

        $this->assertNotNull($service, 'the service should still have been created');
        $this->assertSame($this->mine->id, $service->division_id);
    }

    // ---------------------------------------------------------- fail-closed

    public function test_a_division_role_with_no_division_sees_nothing(): void
    {
        $stranded = User::factory()->create([
            'division_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $stranded->assignRole(Roles::DIVISION_MANAGER);

        Project::factory()->create(['division_id' => $this->mine->id, 'title' => 'Bomi Quarry Expansion']);
        Project::factory()->create(['division_id' => $this->theirs->id, 'title' => 'Ministry Network Rollout']);

        // An unfinished account must show nothing, not everything.
        $this->actingAs($stranded)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertDontSee('Bomi Quarry Expansion')
            ->assertDontSee('Ministry Network Rollout');
    }

    // --------------------------------------------------- group-level staff

    public function test_group_staff_still_see_every_division(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Roles::ADMIN);

        Project::factory()->create(['division_id' => $this->mine->id, 'title' => 'Bomi Quarry Expansion']);
        Project::factory()->create(['division_id' => $this->theirs->id, 'title' => 'Ministry Network Rollout']);

        $this->actingAs($admin)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Bomi Quarry Expansion')
            ->assertSee('Ministry Network Rollout');
    }

    // ------------------------------------------- group records are readable

    public function test_a_group_wide_vacancy_is_visible_but_not_editable(): void
    {
        // No division: a company-wide posting. A division head should be able
        // to read it, and must not be able to rewrite what the other three
        // divisions are also showing.
        $groupWide = Career::factory()->create(['division_id' => null, 'title' => 'Group Internal Auditor']);

        $this->actingAs($this->head)
            ->get(route('admin.careers.index'))
            ->assertOk()
            ->assertSee('Group Internal Auditor');

        $this->actingAs($this->head)
            ->put(route('admin.careers.update', $groupWide), [
                'title' => 'Rewritten',
                'employment_type' => 'full_time',
                'location' => 'Monrovia',
            ])
            ->assertForbidden();
    }
}
