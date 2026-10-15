@extends('admin.layouts.app')

@section('title', 'Training & Certification')
@section('page-subtitle', 'Courses booked and completed, and the certificates that have to be kept current.')

@section('page-actions')
    <a href="{{ route('admin.training.export', $filters) }}" class="btn btn-success">
        <i class="fa-solid fa-file-excel me-2"></i>Download Excel
    </a>
    @can('create', App\Models\EmployeeTraining::class)
        <a href="{{ route('admin.training.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Add training
        </a>
    @endcan
@endsection

@section('content')
    @include('admin.hr._tabs')

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            <div class="metric-card metric-card--accent">
                <div class="metric-card__label">Records</div>
                <div class="metric-card__value">{{ number_format($summary['total']) }}</div>
                <div class="metric-card__hint">Matching filters</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card">
                <div class="metric-card__label">Completed</div>
                <div class="metric-card__value">{{ number_format($summary['completed']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card">
                <div class="metric-card__label">Training hours</div>
                <div class="metric-card__value">{{ number_format($summary['hours'], 1) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="metric-card">
                <div class="metric-card__label">Spend</div>
                <div class="metric-card__value">@money($summary['cost'])</div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <a href="{{ route('admin.training.index', ['expiry' => 'expiring']) }}" class="text-reset text-decoration-none">
                <div class="metric-card">
                    <span class="metric-card__icon bg-warning-subtle text-warning"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div class="metric-card__label">Expiring ≤ 60 days</div>
                    <div class="metric-card__value">{{ $summary['expiring'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-2">
            <a href="{{ route('admin.training.index', ['expiry' => 'expired']) }}" class="text-reset text-decoration-none">
                <div class="metric-card">
                    <span class="metric-card__icon bg-danger-subtle text-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="metric-card__label">Expired</div>
                    <div class="metric-card__value">{{ $summary['expired'] }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="table-card">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Course, provider or employee">
                </div>
                <div class="col-6 col-md-2">
                    <select name="category" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All categories</option>
                        @foreach(App\Models\EmployeeTraining::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['category'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Any status</option>
                        @foreach(App\Models\EmployeeTraining::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="department" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All departments</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" @selected(($filters['department'] ?? null) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="expiry" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Any validity</option>
                        <option value="expiring" @selected(($filters['expiry'] ?? null) === 'expiring')>Expiring soon</option>
                        <option value="expired" @selected(($filters['expiry'] ?? null) === 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    @if(array_filter($filters))
                        <a href="{{ route('admin.training.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Course</th>
                        <th>Dates</th>
                        <th class="text-end">Hours</th>
                        <th>Status</th>
                        <th>Certificate</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $training)
                        <tr>
                            <td>
                                <a href="{{ route('admin.employees.show', $training->employee_id) }}#tab-training" class="fw-semibold">{{ $training->employee?->full_name }}</a>
                                <div class="small text-muted">{{ $training->employee?->department?->name ?? $training->employee?->employee_number }}</div>
                            </td>
                            <td>
                                <div>{{ $training->title }}</div>
                                <div class="small text-muted">{{ $training->category_label }}@if($training->provider) · {{ $training->provider }}@endif</div>
                            </td>
                            <td class="small text-nowrap">
                                {{ $training->start_date?->format('j M Y') ?? '—' }}
                                @if($training->end_date && ! $training->end_date->eq($training->start_date))
                                    <div class="text-muted">to {{ $training->end_date->format('j M Y') }}</div>
                                @endif
                            </td>
                            <td class="text-end small">{{ $training->hours ? number_format($training->hours, 1) : '—' }}</td>
                            <td><span class="badge bg-{{ $training->status_color }}-subtle text-{{ $training->status_color }}">{{ $training->status_label }}</span></td>
                            <td class="small">
                                @if($training->certificate_expiry || $training->certificate_number)
                                    <span class="badge bg-{{ $training->expiry_color }}-subtle text-{{ $training->expiry_color }}">{{ $training->expiry_label }}</span>
                                    @if($training->certificate_number)<div class="text-muted mt-1">{{ $training->certificate_number }}</div>@endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($training->certificate_path)
                                        <a href="{{ route('admin.training.certificate', $training) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Download certificate">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif
                                    @can('update', $training)
                                        <a href="{{ route('admin.training.edit', $training) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $training)
                                        <form method="POST" action="{{ route('admin.training.destroy', $training) }}" data-confirm="Delete this training record?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="fa-solid fa-graduation-cap fa-2x d-block mb-2 opacity-50"></i>No training records{{ array_filter($filters) ? ' match these filters' : ' yet' }}.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
