@extends('admin.layouts.app')

@section('title', $employee->full_name)
@section('page-title', $employee->full_name)
@section('page-subtitle', $employee->employee_number.' · '.($employee->position?->title ?? 'Position not set'))

@section('page-actions')
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>All employees
    </a>
    @can('update', $employee)
        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary">
            <i class="fa-solid fa-pen me-2"></i>Edit record
        </a>
    @endcan
@endsection

@section('content')

    <div class="row g-4">
        {{-- ------------------------------------------ Identity card --}}
        <div class="col-lg-3">
            <div class="card-soft p-4 text-center mb-4">
                <img src="{{ $employee->photo_url }}" alt="" class="rounded-circle mb-3"
                     width="120" height="120" style="object-fit: cover;">

                <h2 class="h5 mb-1">{{ $employee->full_name }}</h2>
                <p class="text-muted small mb-2">{{ $employee->position?->title ?? '—' }}</p>

                <span class="badge bg-{{ $employee->status_color }}-subtle text-{{ $employee->status_color }} mb-3">
                    {{ $employee->status_label }}
                </span>

                <dl class="row small text-start mb-0 border-top pt-3">
                    <dt class="col-6 text-muted fw-normal">Number</dt>
                    <dd class="col-6 text-end">{{ $employee->employee_number }}</dd>
                    <dt class="col-6 text-muted fw-normal">Department</dt>
                    <dd class="col-6 text-end">{{ $employee->department?->name ?? '—' }}</dd>
                    @if($employee->division)
                        <dt class="col-6 text-muted fw-normal">Division</dt>
                        <dd class="col-6 text-end">{{ $employee->division->name }}</dd>
                    @endif
                    <dt class="col-6 text-muted fw-normal">Service</dt>
                    <dd class="col-6 text-end">
                        {{ $employee->years_of_service !== null ? $employee->years_of_service.' yr' : '—' }}
                    </dd>
                </dl>
            </div>

            {{-- Portal access --}}
            <div class="card-soft p-4">
                <h3 class="h6 mb-2">Portal sign-in</h3>
                @if($employee->user)
                    <p class="small text-muted mb-2">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>
                        Active — {{ $employee->user->email }}
                    </p>
                    <dl class="row small mb-0">
                        <dt class="col-7 text-muted fw-normal">Email verified</dt>
                        <dd class="col-5 text-end">{{ $employee->user->hasVerifiedEmail() ? 'Yes' : 'Pending' }}</dd>
                        <dt class="col-7 text-muted fw-normal">Last sign-in</dt>
                        <dd class="col-5 text-end">{{ $employee->user->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                    </dl>
                @else
                    <p class="small text-muted mb-3">
                        No sign-in account. Creating one emails them a link to set their own password.
                    </p>
                    @can('update', $employee)
                        <form method="POST" action="{{ route('admin.employees.login', $employee) }}"
                              data-confirm="Create a portal sign-in and email {{ $employee->email ?: 'this employee' }}?">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"
                                    @disabled(blank($employee->email))>
                                <i class="fa-solid fa-key me-2"></i>Create sign-in account
                            </button>
                        </form>
                        @if(blank($employee->email))
                            <p class="text-danger mt-2 mb-0" style="font-size:.75rem;">
                                Add a work email to the record first.
                            </p>
                        @endif
                    @endcan
                @endif
            </div>
        </div>

        {{-- ------------------------------------------ Tabbed detail --}}
        <div class="col-lg-9">
            <div class="card-soft">
                <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                    @php
                        $tabs = [
                            ['id' => 'overview',   'label' => 'Overview',    'icon' => 'fa-circle-info', 'phase' => 1],
                            ['id' => 'team',       'label' => 'Team',        'icon' => 'fa-sitemap',     'phase' => 1],
                            ['id' => 'activities', 'label' => 'Activity',    'icon' => 'fa-clock-rotate-left', 'phase' => 1],
                            ['id' => 'attendance', 'label' => 'Attendance',  'icon' => 'fa-calendar-check', 'phase' => 2],
                            ['id' => 'leave',      'label' => 'Leave',       'icon' => 'fa-plane-departure', 'phase' => 2],
                            ['id' => 'documents',  'label' => 'Documents',   'icon' => 'fa-folder-open', 'phase' => 2],
                            ['id' => 'performance','label' => 'Performance', 'icon' => 'fa-chart-line',  'phase' => 2],
                            ['id' => 'payroll',    'label' => 'Payroll',     'icon' => 'fa-money-bill',  'phase' => 3],
                        ];
                    @endphp

                    @foreach($tabs as $tab)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }} {{ $tab['phase'] > 1 ? 'text-muted' : '' }}"
                                    data-bs-toggle="tab" data-bs-target="#tab-{{ $tab['id'] }}" type="button" role="tab">
                                <i class="fa-solid {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                                @if($tab['phase'] > 1)
                                    <span class="badge bg-light text-muted ms-1" style="font-size:.6rem;">
                                        Phase {{ $tab['phase'] }}
                                    </span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content p-4">
                    {{-- ---------------------------------------- Overview --}}
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h3 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Personal</h3>
                                <dl class="row small mb-0">
                                    <dt class="col-5 text-muted fw-normal">Full name</dt>
                                    <dd class="col-7">{{ $employee->full_name }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Gender</dt>
                                    <dd class="col-7">{{ ucfirst($employee->gender) }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Marital status</dt>
                                    <dd class="col-7">{{ ucfirst($employee->marital_status) }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Nationality</dt>
                                    <dd class="col-7">{{ $employee->nationality ?: '—' }}</dd>

                                    @can('viewSensitive', $employee)
                                        <dt class="col-5 text-muted fw-normal">Date of birth</dt>
                                        <dd class="col-7">
                                            {{ $employee->date_of_birth?->format('j M Y') ?? '—' }}
                                            @if($employee->age) <span class="text-muted">({{ $employee->age }})</span> @endif
                                        </dd>
                                        <dt class="col-5 text-muted fw-normal">National ID</dt>
                                        <dd class="col-7">{{ $employee->national_id ?: '—' }}</dd>
                                        <dt class="col-5 text-muted fw-normal">Passport</dt>
                                        <dd class="col-7">{{ $employee->passport_number ?: '—' }}</dd>
                                    @else
                                        <dt class="col-5 text-muted fw-normal">Identity documents</dt>
                                        <dd class="col-7 text-muted fst-italic">Restricted</dd>
                                    @endcan

                                    @if($employee->blood_group)
                                        <dt class="col-5 text-muted fw-normal">Blood group</dt>
                                        <dd class="col-7">{{ $employee->blood_group }}</dd>
                                    @endif
                                </dl>
                            </div>

                            <div class="col-md-6">
                                <h3 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Contact</h3>
                                <dl class="row small mb-0">
                                    <dt class="col-5 text-muted fw-normal">Work email</dt>
                                    <dd class="col-7">
                                        @if($employee->email)
                                            <a href="mailto:{{ $employee->email }}">{{ $employee->email }}</a>
                                        @else — @endif
                                    </dd>
                                    <dt class="col-5 text-muted fw-normal">Personal email</dt>
                                    <dd class="col-7">{{ $employee->personal_email ?: '—' }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                                    <dd class="col-7">{{ $employee->phone ?: '—' }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Alternative</dt>
                                    <dd class="col-7">{{ $employee->alternative_phone ?: '—' }}</dd>
                                    <dt class="col-5 text-muted fw-normal">Address</dt>
                                    <dd class="col-7">
                                        {{ collect([$employee->residential_address, $employee->city, $employee->country])->filter()->implode(', ') ?: '—' }}
                                    </dd>
                                </dl>

                                @php($emergency = $employee->emergencyContacts->first())
                                <h3 class="h6 text-uppercase text-muted mb-3 mt-4" style="letter-spacing:.1em;">
                                    Emergency contact
                                </h3>
                                @if($emergency)
                                    <dl class="row small mb-0">
                                        <dt class="col-5 text-muted fw-normal">Name</dt>
                                        <dd class="col-7">{{ $emergency->name }}</dd>
                                        <dt class="col-5 text-muted fw-normal">Relationship</dt>
                                        <dd class="col-7">{{ $emergency->relationship ?: '—' }}</dd>
                                        <dt class="col-5 text-muted fw-normal">Phone</dt>
                                        <dd class="col-7">{{ $emergency->phone }}</dd>
                                    </dl>
                                @else
                                    <p class="small text-muted mb-0">Not recorded.</p>
                                @endif
                            </div>

                            <div class="col-12">
                                <h3 class="h6 text-uppercase text-muted mb-3 pt-3 border-top" style="letter-spacing:.1em;">
                                    Employment
                                </h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row small mb-0">
                                            <dt class="col-5 text-muted fw-normal">Department</dt>
                                            <dd class="col-7">{{ $employee->department?->name ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Division</dt>
                                            <dd class="col-7">{{ $employee->division?->name ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Position</dt>
                                            <dd class="col-7">{{ $employee->position?->title ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Employment type</dt>
                                            <dd class="col-7">{{ $employee->employmentType?->name ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Reports to</dt>
                                            <dd class="col-7">
                                                @if($employee->manager)
                                                    <a href="{{ route('admin.employees.show', $employee->manager) }}">
                                                        {{ $employee->manager->full_name }}
                                                    </a>
                                                @else — @endif
                                            </dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row small mb-0">
                                            <dt class="col-5 text-muted fw-normal">Date hired</dt>
                                            <dd class="col-7">{{ $employee->date_hired?->format('j M Y') ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Confirmed</dt>
                                            <dd class="col-7">{{ $employee->confirmation_date?->format('j M Y') ?? '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Contract expires</dt>
                                            <dd class="col-7">
                                                {{ $employee->contract_expiry?->format('j M Y') ?? '—' }}
                                                @if($employee->contractExpiringWithin(60))
                                                    <span class="badge bg-warning-subtle text-warning">Soon</span>
                                                @endif
                                            </dd>
                                            <dt class="col-5 text-muted fw-normal">Work location</dt>
                                            <dd class="col-7">{{ $employee->work_location ?: '—' }}</dd>
                                            <dt class="col-5 text-muted fw-normal">Hired from</dt>
                                            <dd class="col-7">
                                                {{ $employee->jobApplication ? 'Application '.$employee->jobApplication->reference : 'Direct appointment' }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            @can('viewSalary', $employee)
                                <div class="col-12">
                                    <h3 class="h6 text-uppercase text-muted mb-3 pt-3 border-top" style="letter-spacing:.1em;">
                                        Salary &amp; bank
                                        <span class="badge bg-danger-subtle text-danger ms-1" style="font-size:.6rem;">
                                            Restricted
                                        </span>
                                    </h3>
                                    @if($employee->bankDetails)
                                        <dl class="row small mb-0">
                                            <dt class="col-sm-3 text-muted fw-normal">Basic salary</dt>
                                            <dd class="col-sm-3">
                                                @money($employee->bankDetails->basic_salary, $employee->bankDetails->currency)
                                                <span class="text-muted">/ {{ $employee->bankDetails->pay_frequency }}</span>
                                            </dd>
                                            <dt class="col-sm-3 text-muted fw-normal">Bank</dt>
                                            <dd class="col-sm-3">{{ $employee->bankDetails->bank_name ?: '—' }}</dd>
                                            <dt class="col-sm-3 text-muted fw-normal">Account</dt>
                                            <dd class="col-sm-3">{{ $employee->bankDetails->masked_account_number ?: '—' }}</dd>
                                            <dt class="col-sm-3 text-muted fw-normal">TIN</dt>
                                            <dd class="col-sm-3">{{ $employee->bankDetails->tax_identification_number ?: '—' }}</dd>
                                        </dl>
                                    @else
                                        <p class="small text-muted mb-0">No salary details recorded.</p>
                                    @endif
                                </div>
                            @endcan

                            @if($employee->notes)
                                <div class="col-12">
                                    <h3 class="h6 text-uppercase text-muted mb-2 pt-3 border-top" style="letter-spacing:.1em;">
                                        HR notes
                                    </h3>
                                    <p class="small mb-0">{!! nl2br(e($employee->notes)) !!}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ---------------------------------------- Team --}}
                    <div class="tab-pane fade" id="tab-team" role="tabpanel">
                        <h3 class="h6 mb-3">Direct reports ({{ $employee->directReports->count() }})</h3>

                        @forelse($employee->directReports as $report)
                            <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                <img src="{{ $report->photo_url }}" alt="" class="rounded-circle"
                                     width="40" height="40" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <a href="{{ route('admin.employees.show', $report) }}" class="small fw-semibold">
                                        {{ $report->full_name }}
                                    </a>
                                    <div class="text-muted" style="font-size:.78rem;">{{ $report->position?->title }}</div>
                                </div>
                                <span class="badge bg-{{ $report->status_color }}-subtle text-{{ $report->status_color }}">
                                    {{ $report->status_label }}
                                </span>
                            </div>
                        @empty
                            <div class="empty-state py-4">
                                <i class="fa-solid fa-sitemap"></i>
                                <p class="mb-0">Nobody reports to this employee.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- ---------------------------------------- Activity --}}
                    <div class="tab-pane fade" id="tab-activities" role="tabpanel">
                        <h3 class="h6 mb-3">Record history</h3>

                        @forelse($activities as $activity)
                            <div class="d-flex gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                @php($colour = match($activity->event) {
                                    'created' => 'success', 'updated' => 'info',
                                    'deleted' => 'danger', default => 'secondary',
                                })
                                <span class="badge bg-{{ $colour }}-subtle text-{{ $colour }} align-self-start">
                                    {{ ucfirst($activity->event ?: 'changed') }}
                                </span>
                                <div class="flex-grow-1">
                                    <div class="small">
                                        by <strong>{{ $activity->causer?->name ?? 'System' }}</strong>
                                    </div>
                                    <div class="text-muted" style="font-size:.76rem;">
                                        {{ $activity->created_at->format('j M Y, H:i') }}
                                        · {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                    @php($changed = array_keys($activity->properties['attributes'] ?? []))
                                    @if($changed)
                                        <div class="text-muted mt-1" style="font-size:.76rem;">
                                            {{ collect($changed)->map(fn ($f) => str_replace('_', ' ', $f))->implode(', ') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state py-4">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <p class="mb-0">No changes recorded yet.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- ---------------------------------------- Not yet built --}}
                    @foreach(['attendance' => 2, 'leave' => 2, 'documents' => 2, 'performance' => 2, 'payroll' => 3] as $pane => $phase)
                        <div class="tab-pane fade" id="tab-{{ $pane }}" role="tabpanel">
                            <div class="empty-state py-5">
                                <i class="fa-solid fa-hammer"></i>
                                <p class="mb-1"><strong>{{ ucfirst($pane) }}</strong> arrives in Phase {{ $phase }}.</p>
                                <p class="small mb-0">
                                    The tab is here so the profile layout is settled; it will fill with real data
                                    rather than placeholders when the module is built.
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection
