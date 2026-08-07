<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\User;
use App\Notifications\EmployeeWelcome;
use App\Services\MediaUploader;
use App\Support\Navigation;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

/**
 * Employee records — the HR side of the module.
 *
 * Three things worth knowing:
 *
 *  - Salary and identity documents are gated by their own permissions, so an
 *    HR officer maintains staff details without ever seeing pay or a
 *    passport number.
 *  - A department manager sees only their own department; that is applied to
 *    the query, not merely hidden in the view.
 *  - Creating a portal login is optional and never emails a password. The
 *    new employee receives a link to set their own.
 */
class EmployeeController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->scopedQuery()
            ->with(['department:id,name', 'position:id,title', 'division:id,name'])
            ->search($request->query('q'))
            ->inDepartment($request->query('department'))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('employment_status', $s))
            ->when($request->query('division'), fn (Builder $q, $id) => $q->where('division_id', $id))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.employees.index', [
            'records' => $employees,
            'search' => $request->query('q', ''),
            'departments' => Department::query()->ordered()->pluck('name', 'id'),
            'divisions' => Division::query()->ordered()->pluck('name', 'id'),
            'counts' => $this->scopedQuery()
                ->selectRaw('employment_status, COUNT(*) as total')
                ->groupBy('employment_status')
                ->pluck('total', 'employment_status'),
        ]);
    }

    // ------------------------------------------------------------------
    // Profile
    // ------------------------------------------------------------------

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load([
            'department', 'division', 'position', 'employmentType',
            'manager', 'directReports.position', 'emergencyContacts',
            'user', 'jobApplication',
        ]);

        // Salary is loaded only for those permitted to see it.
        if (auth()->user()->can('viewSalary', $employee)) {
            $employee->load('bankDetails');
        }

        return view('admin.employees.show', [
            'employee' => $employee,
            'activities' => Activity::query()
                ->where('subject_type', 'employee')
                ->where('subject_id', $employee->id)
                ->with('causer:id,name')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    // ------------------------------------------------------------------
    // Create & update
    // ------------------------------------------------------------------

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('admin.employees.create', $this->formData(new Employee));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $data = $request->validate($this->rules($request, null));

        $employee = DB::transaction(function () use ($request, $data) {
            $employee = new Employee;
            $employee->fill($this->employeeAttributes($data));

            if ($request->hasFile('photo')) {
                $employee->photo = $this->uploader->image($request->file('photo'), 'employees/photos', 800);
            }

            $employee->save();

            $this->syncEmergencyContact($employee, $data);
            $this->syncBankDetails($employee, $data, $request);

            // Optional portal login.
            if ($request->boolean('generate_login')) {
                $this->createLoginFor($employee, $data['password'] ?? null);
            }

            return $employee;
        });

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', $employee->full_name.' has been added as '.$employee->employee_number
                .($employee->user_id ? '. A welcome email with sign-in details has been sent.' : '.'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load(['emergencyContacts', 'bankDetails']);

        return view('admin.employees.edit', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $data = $request->validate($this->rules($request, $employee));

        DB::transaction(function () use ($request, $employee, $data) {
            $employee->fill($this->employeeAttributes($data));

            if ($request->boolean('remove_photo')) {
                $this->uploader->deleteFrom('public', $employee->photo);
                $employee->photo = null;
            }

            if ($request->hasFile('photo')) {
                $employee->photo = $this->uploader->image(
                    $request->file('photo'), 'employees/photos', 800, replacing: $employee->photo
                );
            }

            $employee->save();

            $this->syncEmergencyContact($employee, $data);
            $this->syncBankDetails($employee, $data, $request);

            if ($request->boolean('generate_login') && ! $employee->user_id) {
                $this->createLoginFor($employee, $data['password'] ?? null);
            }
        });

        return redirect()
            ->route('admin.employees.show', $employee)
            ->with('success', 'Employee record updated.');
    }

    /** Retire an employee without destroying their history. */
    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        DB::transaction(function () use ($employee) {
            // Deactivate the login rather than delete it — the audit trail,
            // announcements and any authored records still reference it.
            $employee->user?->update(['is_active' => false]);

            $employee->update(['is_active' => false, 'employment_status' => 'resigned']);
            $employee->delete();
        });

        return redirect()
            ->route('admin.employees.index')
            ->with('success', $employee->full_name.' has been archived and their sign-in disabled.');
    }

    /** Create a portal login for an employee who does not have one. */
    public function createLogin(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        if ($employee->user_id) {
            return back()->with('error', 'This employee already has a sign-in account.');
        }

        if (blank($employee->email)) {
            return back()->with('error', 'Add a work email address to the record first.');
        }

        $validated = $request->validate([
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $password = $validated['password'] ?? null;

        $this->createLoginFor($employee, $password);

        return back()->with('success', $password
            ? 'Sign-in account created. Give '.$employee->first_name.' the password you set — they will be asked to change it when they first sign in.'
            : 'Sign-in account created. '.$employee->full_name.' has been emailed a link to set their password.');
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Creates the user account for an employee.
     *
     * Two ways in, and the caller picks by passing a password or not:
     *
     *  - No password: the employee is emailed a signed, expiring link and
     *    chooses their own. Preferred, because a password nobody else ever
     *    knew cannot be leaked by anyone else.
     *  - A password set by HR: for staff with no reliable email — a plant
     *    operator with a shared inbox cannot follow a link that expires in an
     *    hour. The account is flagged must_change_password, so the password HR
     *    knows stops working the moment the employee signs in.
     */
    protected function createLoginFor(Employee $employee, ?string $password = null): void
    {
        // Both paths below read these, and in the update() path the employee
        // came from route binding with nothing eager loaded.
        $employee->loadMissing(['position', 'department']);

        /*
         * withTrashed matters: accounts are retired, not removed (see
         * UserController::destroy), so a deleted row — and the unique index on
         * its address — outlives the deletion. Looking the address up without
         * it misses a retired account and the insert below collides with the
         * index instead.
         */
        $user = User::withTrashed()->where('email', $employee->email)->first();

        if (! $user) {
            $user = new User;
            $user->forceFill([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'position' => $employee->position?->title,
                'department' => $employee->department?->name,
                'division_id' => $employee->division_id,
                // Unusable by design when HR sets none: the account can only
                // be entered through the emailed link.
                'password' => $password ?? Str::random(48),
                'must_change_password' => filled($password),
                'is_active' => true,
            ])->save();
        } else {
            /*
             * Re-hiring, or a visitor who already had a client account. Bring
             * the row back and point it at the current employment record —
             * a restored account left deactivated or showing a former posting
             * is worse than no account at all.
             */
            if ($user->trashed()) {
                $user->restore();
            }

            $attributes = [
                'phone' => $employee->phone,
                'position' => $employee->position?->title,
                'department' => $employee->department?->name,
                'division_id' => $employee->division_id,
                'is_active' => true,
            ];

            // The account's own name is left alone: the person chose it, and
            // for a returning employee it is already right.
            if (filled($password)) {
                $attributes['password'] = $password;
                $attributes['must_change_password'] = true;
            }

            $user->forceFill($attributes)->save();
        }

        // Employee role only — a portal login grants no administrative rights.
        if ($user->roles->isEmpty()) {
            $user->assignRole(Roles::EMPLOYEE);
        }

        $employee->forceFill(['user_id' => $user->id])->save();

        $user->sendEmailVerificationNotification();
        $user->notify(new EmployeeWelcome($employee->fresh(['department', 'position']), passwordSetByHr: filled($password)));
    }

    protected function employeeAttributes(array $data): array
    {
        return collect($data)->only([
            'first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth',
            'nationality', 'national_id', 'passport_number', 'marital_status', 'blood_group',
            'email', 'personal_email', 'phone', 'alternative_phone',
            'residential_address', 'city', 'country',
            'department_id', 'division_id', 'position_id', 'employment_type_id',
            'reporting_manager_id', 'date_hired', 'confirmation_date', 'contract_expiry',
            'employment_status', 'exit_date', 'work_location', 'notes', 'is_active',
        ])->all();
    }

    protected function syncEmergencyContact(Employee $employee, array $data): void
    {
        if (blank($data['emergency_name'] ?? null) || blank($data['emergency_phone'] ?? null)) {
            return;
        }

        $employee->emergencyContacts()->updateOrCreate(
            ['is_primary' => true],
            [
                'name' => $data['emergency_name'],
                'relationship' => $data['emergency_relationship'] ?? null,
                'phone' => $data['emergency_phone'],
                'alternative_phone' => $data['emergency_alternative_phone'] ?? null,
            ]
        );
    }

    /** Only written by someone holding the salary permission. */
    protected function syncBankDetails(Employee $employee, array $data, Request $request): void
    {
        if (! $request->user()->can('updateSalary', $employee)) {
            return;
        }

        $fields = collect($data)->only([
            'basic_salary', 'currency', 'pay_frequency', 'bank_name', 'bank_branch',
            'account_name', 'account_number', 'tax_identification_number',
            'social_security_number',
        ])->filter(fn ($value) => $value !== null && $value !== '');

        if ($fields->isEmpty()) {
            return;
        }

        $employee->bankDetails()->updateOrCreate(['employee_id' => $employee->id], $fields->all());
    }

    protected function rules(Request $request, ?Employee $employee): array
    {
        $rules = [
            // Personal
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:3072'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'undisclosed'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:80'],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed', 'undisclosed'])],
            'blood_group' => ['nullable', 'string', 'max:5'],

            // Contact
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'personal_email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'alternative_phone' => ['nullable', 'string', 'max:40'],
            'residential_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],

            'emergency_name' => ['nullable', 'string', 'max:150'],
            'emergency_relationship' => ['nullable', 'string', 'max:80'],
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'emergency_alternative_phone' => ['nullable', 'string', 'max:40'],

            // Employment
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'employment_type_id' => ['required', 'integer', Rule::exists('employment_types', 'id')],
            'reporting_manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'date_hired' => ['required', 'date'],
            'confirmation_date' => ['nullable', 'date', 'after_or_equal:date_hired'],
            'contract_expiry' => ['nullable', 'date', 'after:date_hired'],
            'employment_status' => ['required', Rule::in(Employee::STATUSES)],
            'exit_date' => ['nullable', 'date', 'after_or_equal:date_hired'],
            'work_location' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'generate_login' => ['boolean'],
        ];

        // Salary fields are only accepted from someone allowed to set them.
        if ($request->user()->can('updateSalary', $employee ?? Employee::class)) {
            $rules += [
                'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
                'currency' => ['nullable', 'string', 'size:3'],
                'pay_frequency' => ['nullable', Rule::in(['monthly', 'fortnightly', 'weekly', 'daily'])],
                'bank_name' => ['nullable', 'string', 'max:150'],
                'bank_branch' => ['nullable', 'string', 'max:150'],
                'account_name' => ['nullable', 'string', 'max:150'],
                'account_number' => ['nullable', 'string', 'max:80'],
                'tax_identification_number' => ['nullable', 'string', 'max:80'],
                'social_security_number' => ['nullable', 'string', 'max:80'],
            ];
        }

        // A login can only be generated when there is an address to send to.
        if ($request->boolean('generate_login')) {
            $rules['email'] = ['required', 'email:rfc', 'max:190',
                Rule::unique('employees', 'email')->ignore($employee?->id)];

            // Optional: left blank, the employee sets their own through the
            // emailed link. Held to the same strength as any other password —
            // being chosen by HR is no reason for it to be weak.
            $rules['password'] = ['nullable', 'confirmed',
                Password::min(8)->mixedCase()->numbers()];
        }

        return $rules;
    }

    protected function formData(Employee $employee): array
    {
        return [
            'record' => $employee,
            'departments' => Department::query()->active()->ordered()->pluck('name', 'id'),
            'divisions' => Division::query()->active()->ordered()->pluck('name', 'id'),
            // Eager-load the department: the label shows it, and lazy loading
            // is disabled outside production so an N+1 fails loudly.
            'positions' => Position::query()->active()->with('department:id,name')->ordered()->get()
                ->mapWithKeys(fn (Position $p) => [$p->id => $p->title.($p->department ? ' — '.$p->department->name : '')]),
            'employmentTypes' => EmploymentType::query()->active()->ordered()->pluck('name', 'id'),
            'managers' => Employee::query()->employed()->with('position:id,title')->ordered()
                ->when($employee->exists, fn (Builder $q) => $q->whereKeyNot($employee->getKey()))
                ->get()
                ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name.' — '.($e->position?->title ?? 'Staff')]),
            'emergency' => $employee->exists
                ? ($employee->emergencyContacts->firstWhere('is_primary', true) ?? $employee->emergencyContacts->first())
                : null,
        ];
    }

    /**
     * Narrowed to what this user may see.
     *
     * Both confinements are applied, and they compose: a division head who is
     * also a department manager sees the intersection. Each uses `?? 0` for
     * the missing case so an incomplete account matches nothing rather than
     * everything — the same fail-closed rule as DivisionScope.
     */
    protected function scopedQuery(): Builder
    {
        $user = auth()->user();

        $query = Employee::query()->withinDivisionOf($user);

        if ($user->hasRole(Roles::DEPARTMENT_MANAGER) && ! $user->hasAnyRole(Navigation::GROUP_ROLES)) {
            $query->where('department_id', $user->employee?->department_id ?? 0);
        }

        return $query;
    }
}
