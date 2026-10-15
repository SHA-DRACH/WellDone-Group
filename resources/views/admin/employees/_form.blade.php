@php($isNew = ! $record->exists)
@php($canSalary = auth()->user()->can('updateSalary', $record->exists ? $record : App\Models\Employee::class))
@php($bank = $record->exists ? $record->bankDetails : null)

<form method="POST"
      action="{{ $isNew ? route('admin.employees.store') : route('admin.employees.update', $record) }}"
      enctype="multipart/form-data" novalidate>
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- ------------------------------------------ Personal --}}
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                    <i class="fa-solid fa-user me-2"></i>Personal information
                </h2>

                <div class="row">
                    <div class="col-md-4">
                        <x-form.input name="first_name" label="First name" :value="$record->first_name" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="middle_name" label="Middle name" :value="$record->middle_name" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="last_name" label="Last name" :value="$record->last_name" required />
                    </div>

                    <div class="col-md-4">
                        <x-form.select name="gender" label="Gender" required
                                       :value="$record->gender ?: 'undisclosed'"
                                       :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'undisclosed' => 'Prefer not to say']" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="date_of_birth" type="date" label="Date of birth"
                                      :value="$record->date_of_birth?->toDateString()" />
                    </div>
                    <div class="col-md-4">
                        <x-form.select name="marital_status" label="Marital status" required
                                       :value="$record->marital_status ?: 'undisclosed'"
                                       :options="['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'undisclosed' => 'Prefer not to say']" />
                    </div>

                    <div class="col-md-4">
                        <x-form.input name="nationality" label="Nationality"
                                      :value="$record->nationality ?: 'Liberian'" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="national_id" label="National ID number" :value="$record->national_id" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="passport_number" label="Passport number" :value="$record->passport_number" />
                    </div>

                    <div class="col-md-4">
                        <x-form.input name="blood_group" label="Blood group" :value="$record->blood_group"
                                      placeholder="O+" help="Optional." />
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------ Contact --}}
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                    <i class="fa-solid fa-address-book me-2"></i>Contact
                </h2>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="email" type="email" label="Work email" :value="$record->email"
                                      help="Also the sign-in address if a portal account is created." />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="personal_email" type="email" label="Personal email"
                                      :value="$record->personal_email" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="phone" label="Phone" :value="$record->phone" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="alternative_phone" label="Alternative phone"
                                      :value="$record->alternative_phone" />
                    </div>
                    <div class="col-12">
                        <x-form.textarea name="residential_address" label="Residential address"
                                         :value="$record->residential_address" :rows="2" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="city" label="City" :value="$record->city" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="country" label="Country" :value="$record->country ?: 'Liberia'" />
                    </div>
                </div>

                <h3 class="h6 mt-3 mb-3 pt-3 border-top">Emergency contact</h3>
                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="emergency_name" label="Name" :value="$emergency?->name" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="emergency_relationship" label="Relationship"
                                      :value="$emergency?->relationship" placeholder="Spouse, parent, sibling…" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="emergency_phone" label="Phone" :value="$emergency?->phone" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="emergency_alternative_phone" label="Alternative phone"
                                      :value="$emergency?->alternative_phone" />
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------ Employment --}}
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                    <i class="fa-solid fa-briefcase me-2"></i>Employment
                </h2>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.select name="department_id" label="Department" required
                                       :value="$record->department_id" :options="$departments->all()"
                                       placeholder="Select a department" />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="division_id" label="Business division"
                                       :value="$record->division_id" :options="$divisions->all()"
                                       placeholder="Central function (no division)" />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="position_id" label="Position" required
                                       :value="$record->position_id" :options="$positions->all()"
                                       placeholder="Select a position" />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="employment_type_id" label="Employment type" required
                                       :value="$record->employment_type_id" :options="$employmentTypes->all()"
                                       placeholder="Select a type" />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="reporting_manager_id" label="Reporting manager"
                                       :value="$record->reporting_manager_id" :options="$managers->all()"
                                       placeholder="No line manager" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="work_location" label="Work location" :value="$record->work_location"
                                      placeholder="Monrovia Head Office" />
                    </div>

                    <div class="col-md-4">
                        <x-form.input name="date_hired" type="date" label="Date hired" required
                                      :value="$record->date_hired?->toDateString() ?: now()->toDateString()" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="confirmation_date" type="date" label="Confirmation date"
                                      :value="$record->confirmation_date?->toDateString()"
                                      help="When probation ends." />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="contract_expiry" type="date" label="Contract expires"
                                      :value="$record->contract_expiry?->toDateString()"
                                      help="Fixed-term contracts only." />
                    </div>

                    <div class="col-12">
                        <x-form.textarea name="notes" label="HR notes" :value="$record->notes" :rows="3"
                                         help="Internal — never shown to the employee." />
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------ Salary --}}
            @if($canSalary)
                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-1" style="letter-spacing:.1em;">
                        <i class="fa-solid fa-money-bill me-2"></i>Salary &amp; bank
                        <span class="badge bg-danger-subtle text-danger ms-1" style="font-size:.6rem;">Restricted</span>
                    </h2>
                    <p class="small text-muted mb-3">
                        Groundwork for Payroll in Phase 3. Nothing is calculated from these yet.
                        Visible only to roles holding the salary permission.
                    </p>

                    <div class="row">
                        <div class="col-md-4">
                            <x-form.input name="basic_salary" type="number" step="0.01" min="0"
                                          label="Basic salary" :value="$bank?->basic_salary" />
                        </div>
                        <div class="col-md-4">
                            <x-form.select name="currency" label="Currency" :value="$bank?->currency ?: 'USD'"
                                           :options="['USD' => 'USD', 'LRD' => 'LRD', 'EUR' => 'EUR', 'GBP' => 'GBP']" />
                        </div>
                        <div class="col-md-4">
                            <x-form.select name="pay_frequency" label="Pay frequency"
                                           :value="$bank?->pay_frequency ?: 'monthly'"
                                           :options="['monthly' => 'Monthly', 'fortnightly' => 'Fortnightly', 'weekly' => 'Weekly', 'daily' => 'Daily']" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="bank_name" label="Bank" :value="$bank?->bank_name" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="bank_branch" label="Branch" :value="$bank?->bank_branch" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="account_name" label="Account name" :value="$bank?->account_name" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="account_number" label="Account number" :value="$bank?->account_number" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="tax_identification_number" label="Tax identification number"
                                          :value="$bank?->tax_identification_number" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="social_security_number" label="Social security number"
                                          :value="$bank?->social_security_number" />
                        </div>
                        <div class="col-md-6">
                            <x-form.textarea name="allowances_text" label="Recurring monthly allowances" :rows="3"
                                             :value="collect($bank?->allowances ?? [])->map(fn ($a) => ($a['label'] ?? '').': '.($a['amount'] ?? 0))->implode(PHP_EOL)"
                                             placeholder="Housing: 150"
                                             help="One per line, as Label: amount. Added to every payslip." />
                        </div>
                        <div class="col-md-6">
                            <x-form.textarea name="deductions_text" label="Recurring monthly deductions" :rows="3"
                                             :value="collect($bank?->deductions ?? [])->map(fn ($a) => ($a['label'] ?? '').': '.($a['amount'] ?? 0))->implode(PHP_EOL)"
                                             placeholder="Staff loan: 50"
                                             help="Besides income tax and social security, which payroll works out." />
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ------------------------------------------ Side --}}
        <div class="col-lg-4">
            <div class="card-soft p-4 mb-4">
                <x-form.image name="photo" label="Passport photograph"
                              :current="$record->exists ? $record->photo_url : null"
                              help="Square works best — shown as a circle throughout." />

                <x-form.select name="employment_status" label="Employment status" required
                               :value="$record->employment_status ?: 'probation'"
                               :options="collect(App\Models\Employee::STATUSES)
                                   ->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all()" />

                <x-form.input name="exit_date" type="date" label="Exit date" :value="$record->exit_date?->toDateString()"
                              help="Only for leavers." />

                <x-form.checkbox name="is_active" label="Active employee"
                                 :value="$record->exists ? $record->is_active : true" />
            </div>

            {{-- Portal login --}}
            @if($isNew || ! $record->user_id)
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-2"><i class="fa-solid fa-key me-2"></i>Portal sign-in</h2>
                    <x-form.checkbox name="generate_login" label="Create a sign-in account"
                                     :value="false"
                                     help="Requires a work email address." />

                    <hr class="my-3">

                    <x-form.input name="password" type="password" label="Set a password (optional)"
                                  autocomplete="new-password"
                                  help="At least 8 characters, upper and lower case, and a number." />
                    <x-form.input name="password_confirmation" type="password" label="Confirm password"
                                  autocomplete="new-password" />

                    <p class="text-muted mb-0" style="font-size:.75rem;">
                        Leave both blank and the employee is emailed an expiring link to choose their
                        own password — better, because then nobody else ever knows it. Set one here
                        only for staff who cannot reliably receive email; they will be made to change
                        it the first time they sign in.
                    </p>
                </div>
            @else
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-2"><i class="fa-solid fa-key me-2"></i>Portal sign-in</h2>
                    <p class="small text-muted mb-0">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>
                        Active — {{ $record->user->email }}
                    </p>
                </div>
            @endif

            <div class="card-soft p-4">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $isNew ? 'Create employee' : 'Save changes' }}
                </button>
                <a href="{{ $isNew ? route('admin.employees.index') : route('admin.employees.show', $record) }}"
                   class="btn btn-outline-secondary w-100">Cancel</a>

                @unless($isNew)
                    <hr>
                    <dl class="row small mb-0 text-muted">
                        <dt class="col-6 fw-normal">Number</dt>
                        <dd class="col-6 text-end">{{ $record->employee_number }}</dd>
                        <dt class="col-6 fw-normal">Added</dt>
                        <dd class="col-6 text-end">{{ $record->created_at?->format('j M Y') }}</dd>
                    </dl>
                @endunless
            </div>
        </div>
    </div>
</form>
