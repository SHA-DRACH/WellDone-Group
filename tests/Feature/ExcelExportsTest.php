<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DivisionRecord;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * The Excel downloads. Each test opens the .xlsx that came back and reads
 * the sheet, so what is checked is what somebody would see in Excel.
 */
class ExcelExportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Ada Admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->admin->assignRole(Roles::ADMIN);
    }

    public function test_the_user_export_lists_every_account(): void
    {
        $client = User::factory()->create(['name' => 'Cleo Client', 'email' => 'cleo@example.com', 'is_active' => true]);
        $client->assignRole(Roles::CLIENT);

        $inactive = User::factory()->create(['name' => 'Ivan Inactive', 'is_active' => false]);

        $sheet = $this->sheet($this->actingAs($this->admin)->get(route('admin.users.export')));

        $this->assertStringContainsString('Ada Admin', $sheet);
        $this->assertStringContainsString('Cleo Client', $sheet);
        $this->assertStringContainsString('cleo@example.com', $sheet);
        $this->assertStringContainsString('Ivan Inactive', $sheet);
        $this->assertStringContainsString('Inactive', $sheet);
        $this->assertStringNotContainsString('$2y$', $sheet, 'No password hash may appear in the export.');
    }

    public function test_the_user_export_follows_the_list_filters(): void
    {
        User::factory()->create(['name' => 'Ivan Inactive', 'is_active' => false]);

        $sheet = $this->sheet($this->actingAs($this->admin)->get(route('admin.users.export', ['status' => 'active'])));

        $this->assertStringContainsString('Ada Admin', $sheet);
        $this->assertStringNotContainsString('Ivan Inactive', $sheet);
    }

    public function test_someone_without_user_access_cannot_export_users(): void
    {
        $officer = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $officer->assignRole(Roles::HR_OFFICER);

        $this->actingAs($officer)->get(route('admin.users.export'))->assertForbidden();
    }

    public function test_a_division_log_exports_only_that_divisions_entries(): void
    {
        $mining = Division::factory()->create(['slug' => 'mining-quarry', 'name' => 'Mining & Quarry', 'is_active' => true]);
        $ict = Division::factory()->create(['slug' => 'ict-services', 'name' => 'ICT Services', 'is_active' => true]);

        DivisionRecord::query()->create([
            'division_id' => $mining->id, 'record_date' => today(),
            'data' => ['site' => 'Kakata Pit 2', 'shift' => 'Day', 'material' => 'Gravel', 'tonnes_extracted' => 120.5],
        ]);
        DivisionRecord::query()->create([
            'division_id' => $ict->id, 'record_date' => today(),
            'data' => ['client' => 'Ministry of Health', 'job_type' => 'Installation', 'status' => 'Open', 'description' => 'LAN'],
        ]);

        $sheet = $this->sheet($this->actingAs($this->admin)->get(route('admin.division.records.export', $mining->slug)));

        $this->assertStringContainsString('Kakata Pit 2', $sheet);
        $this->assertStringContainsString('Tonnes extracted', $sheet);
        $this->assertStringNotContainsString('Ministry of Health', $sheet);
    }

    /** The first worksheet's XML, from a downloaded .xlsx. */
    protected function sheet(TestResponse $response): string
    {
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'The download is not a valid .xlsx file.');

        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        return html_entity_decode((string) $xml, ENT_QUOTES | ENT_XML1);
    }
}
