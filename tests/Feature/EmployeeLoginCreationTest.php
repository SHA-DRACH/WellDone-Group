<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EmployeeController;
use App\Models\Employee;
use App\Models\User;
use App\Services\MediaUploader;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The guarantee this suite protects: giving an employee a portal login never
 * collides with an account that already holds their email address.
 *
 * Accounts are retired rather than removed, so a deleted user keeps its row —
 * and its claim on the unique index. HR creating an employee with that address
 * used to hit the index and get a 500.
 */
class EmployeeLoginCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate(Roles::EMPLOYEE, 'web');
    }

    protected function employee(string $email): Employee
    {
        return Employee::query()->create([
            'first_name' => 'Shadrach',
            'last_name' => 'Jimice',
            'email' => $email,
            'phone' => '0778079617',
            'employment_status' => 'active',
            'is_active' => true,
        ]);
    }

    /** createLoginFor is an internal step of store()/update(); reach it directly. */
    protected function createLoginFor(Employee $employee, ?string $password = null): void
    {
        $controller = new EmployeeController(app(MediaUploader::class));

        $method = new ReflectionMethod($controller, 'createLoginFor');
        $method->invoke($controller, $employee, $password);
    }

    public function test_it_reuses_a_retired_account_instead_of_colliding_with_its_email(): void
    {
        $retired = User::factory()->create([
            'name' => 'Shadrach Jimice Jr',
            'email' => 'returning@example.com',
            'is_active' => false,
        ]);
        $retired->delete();

        $employee = $this->employee('returning@example.com');

        $this->createLoginFor($employee);

        $this->assertSame(1, User::withTrashed()->where('email', 'returning@example.com')->count());
        $this->assertSame($retired->id, $employee->fresh()->user_id);
    }

    public function test_a_retired_account_is_restored_and_reactivated(): void
    {
        $retired = User::factory()->create(['email' => 'returning@example.com', 'is_active' => false]);
        $retired->delete();

        $this->createLoginFor($this->employee('returning@example.com'));

        $restored = User::query()->where('email', 'returning@example.com')->first();

        $this->assertNotNull($restored, 'The account should no longer be trashed.');
        $this->assertTrue($restored->is_active, 'A restored login the employee cannot use is no login at all.');
        $this->assertTrue($restored->hasRole(Roles::EMPLOYEE));
    }

    public function test_it_refreshes_the_account_with_the_current_employment_details(): void
    {
        $retired = User::factory()->create([
            'email' => 'returning@example.com',
            'phone' => '0000000000',
        ]);
        $retired->delete();

        $this->createLoginFor($this->employee('returning@example.com'));

        $this->assertSame('0778079617', User::query()->where('email', 'returning@example.com')->value('phone'));
    }

    public function test_it_still_creates_an_account_when_the_address_is_new(): void
    {
        $employee = $this->employee('brand.new@example.com');

        $this->createLoginFor($employee);

        $user = User::query()->where('email', 'brand.new@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertSame($user->id, $employee->fresh()->user_id);
        $this->assertFalse($user->must_change_password, 'With no password set, the employee chooses their own.');
    }

    public function test_a_password_set_by_hr_must_be_changed_on_first_sign_in(): void
    {
        $this->createLoginFor($this->employee('brand.new@example.com'), 'HandedOver1');

        $this->assertTrue(User::query()->where('email', 'brand.new@example.com')->value('must_change_password'));
    }
}
