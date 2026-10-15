@extends('admin.layouts.app')

@section('title', 'HR Dashboard')
@section('page-subtitle', 'Workforce across every division of the group.')

@section('page-actions')
    @can('create', App\Models\Employee::class)
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-2"></i>Add employee
        </a>
    @endcan
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-primary">
        <i class="fa-solid fa-users me-2"></i>All employees
    </a>
@endsection

@section('content')

    @include('admin.hr._tabs')

    {{-- ------------------------------------------ Headline tiles --}}
    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => 'Total employees',   'value' => $stats['total'],                'icon' => 'fa-users',            'colour' => 'primary',   'route' => 'admin.employees.index'],
                ['label' => 'Hired this month',  'value' => $stats['new_this_month'],       'icon' => 'fa-user-plus',        'colour' => 'success',   'route' => null],
                ['label' => 'On probation',      'value' => $stats['probation'],            'icon' => 'fa-hourglass-half',   'colour' => 'info',      'route' => null],
                ['label' => 'Open applications', 'value' => $stats['pending_applications'], 'icon' => 'fa-file-lines',       'colour' => 'warning',   'route' => 'admin.applications.index'],
                ['label' => 'Open vacancies',    'value' => $stats['open_vacancies'],       'icon' => 'fa-briefcase',        'colour' => 'secondary', 'route' => 'admin.careers.index'],
                ['label' => 'Birthdays today',   'value' => $stats['birthdays_today'],      'icon' => 'fa-cake-candles',     'colour' => 'success',   'route' => null],
                ['label' => 'Contracts expiring','value' => $stats['contracts_expiring'],   'icon' => 'fa-file-circle-exclamation', 'colour' => 'danger', 'route' => null],
                ['label' => 'Without a sign-in', 'value' => $stats['without_login'],        'icon' => 'fa-user-lock',        'colour' => 'dark',      'route' => null],
            ];
        @endphp

        @foreach($tiles as $tile)
            <div class="col-6 col-lg-3">
                @if($tile['route'] && Route::has($tile['route']))
                    <a href="{{ route($tile['route']) }}" class="stat-tile h-100">
                @else
                    <div class="stat-tile h-100">
                @endif
                    <span class="stat-tile__icon bg-{{ $tile['colour'] }}-subtle text-{{ $tile['colour'] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="stat-tile__value">{{ number_format($tile['value']) }}</div>
                        <div class="stat-tile__label">{{ $tile['label'] }}</div>
                    </div>
                @if($tile['route'] && Route::has($tile['route']))
                    </a>
                @else
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ------------------------------------------ Compliance: what is lapsing --}}
    @if($expiring->isNotEmpty())
        <div class="card-soft p-4 mb-4 border-start border-4 border-warning">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-0"><i class="fa-solid fa-bell text-warning me-2"></i>Certificates & documents needing attention</h2>
                    <small class="text-muted">Expired, or expiring within 60 days.</small>
                </div>
                <div class="d-flex gap-2">
                    @can('training.view')
                        <a href="{{ route('admin.training.index', ['expiry' => 'expiring']) }}" class="btn btn-sm btn-outline-secondary">Training</a>
                    @endcan
                    @can('employee_documents.view')
                        <a href="{{ route('admin.employee-documents.index', ['expiry' => 'expiring']) }}" class="btn btn-sm btn-outline-secondary">Documents</a>
                    @endcan
                </div>
            </div>
            <div class="row g-2">
                @foreach($expiring as $item)
                    <div class="col-md-6 col-xl-4">
                        <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 h-100">
                            <div class="small">
                                <div class="fw-semibold">{{ $item['employee'] }}</div>
                                <div class="text-muted">{{ $item['what'] }}</div>
                            </div>
                            <span class="badge bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} text-wrap text-end">{{ $item['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            {{-- ------------------------------------------ Headcount --}}
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">Headcount by department</h2>

                @php($peak = max(1, $byDepartment->max('employees_count') ?: 1))
                @forelse($byDepartment as $department)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $department->name }}</span>
                            <strong>{{ $department->employees_count }}</strong>
                        </div>
                        <div class="progress" style="height: 7px;" role="progressbar"
                             aria-valuenow="{{ $department->employees_count }}" aria-valuemin="0" aria-valuemax="{{ $peak }}">
                            <div class="progress-bar" style="width: {{ round($department->employees_count / $peak * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No departments recorded.</p>
                @endforelse
            </div>

            {{-- ------------------------------------------ Recent hires --}}
            <div class="table-card mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Recently hired</h2>
                    <a href="{{ route('admin.employees.index') }}" class="btn btn-sm btn-outline-primary">All employees</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th class="text-end">Hired</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentHires as $employee)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $employee->photo_url }}" alt="" class="rounded-circle"
                                                 width="34" height="34" style="object-fit: cover;">
                                            <div>
                                                <a href="{{ route('admin.employees.show', $employee) }}" class="fw-semibold">
                                                    {{ $employee->full_name }}
                                                </a>
                                                <div class="small text-muted">{{ $employee->employee_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small">{{ $employee->department?->name ?? '—' }}</td>
                                    <td class="small">{{ $employee->position?->title ?? '—' }}</td>
                                    <td class="text-end small text-muted">{{ $employee->date_hired?->format('j M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-users"></i>
                                            <p class="mb-2">No employees on record yet.</p>
                                            @can('create', App\Models\Employee::class)
                                                <a href="{{ route('admin.employees.create') }}" class="btn btn-sm btn-primary">
                                                    Add the first employee
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ------------------------------------------ Expiring contracts --}}
            @if($expiringContracts->isNotEmpty())
                <div class="card-soft p-4">
                    <h2 class="h6 mb-3">
                        <i class="fa-solid fa-file-circle-exclamation text-danger me-2"></i>Contracts expiring soon
                    </h2>
                    @foreach($expiringContracts as $employee)
                        <div class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <div>
                                <a href="{{ route('admin.employees.show', $employee) }}" class="small fw-semibold">
                                    {{ $employee->full_name }}
                                </a>
                                <div class="text-muted" style="font-size:.76rem;">
                                    {{ $employee->position?->title }} · {{ $employee->department?->name }}
                                </div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger">
                                {{ $employee->contract_expiry->format('j M Y') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ------------------------------------------ Side --}}
        <div class="col-xl-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">By status</h2>
                @php($statusTotal = max(1, $byStatus->sum()))
                @forelse($byStatus as $status => $count)
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                        <span>
                            <strong>{{ $count }}</strong>
                            <span class="text-muted">({{ round($count / $statusTotal * 100) }}%)</span>
                        </span>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No employees yet.</p>
                @endforelse
            </div>

            @if($byGender->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-3">Gender distribution</h2>
                    @foreach($byGender as $gender => $count)
                        <div class="d-flex justify-content-between small mb-2">
                            <span>{{ ucfirst($gender) }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($birthdays->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-3"><i class="fa-solid fa-cake-candles text-success me-2"></i>Birthdays ahead</h2>
                    @foreach($birthdays as $employee)
                        <div class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <div class="small">
                                {{ $employee->full_name }}
                                <div class="text-muted" style="font-size:.74rem;">{{ $employee->department?->name }}</div>
                            </div>
                            <span class="small text-muted">{{ $employee->date_of_birth->format('j M') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($recentApplications->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-3">Latest applications</h2>
                    @foreach($recentApplications as $application)
                        <div class="d-flex justify-content-between align-items-start {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <div>
                                <a href="{{ route('admin.applications.show', $application) }}" class="small fw-semibold">
                                    {{ $application->full_name }}
                                </a>
                                <div class="text-muted" style="font-size:.74rem;">
                                    {{ \Illuminate\Support\Str::limit($application->career?->title ?? 'General', 30) }}
                                </div>
                            </div>
                            <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                                {{ $application->status_label }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card-soft p-4">
                <h2 class="h6 mb-2">Coming in Phase 2</h2>
                <p class="small text-muted mb-2">
                    Attendance, leave balances and performance figures will appear here once
                    those modules are built.
                </p>
                <p class="text-muted mb-0" style="font-size:.75rem;">
                    They are deliberately absent rather than shown empty — a tile reading zero
                    is easily mistaken for a real figure.
                </p>
            </div>
        </div>
    </div>

@endsection
