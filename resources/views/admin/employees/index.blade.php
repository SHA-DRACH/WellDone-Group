@extends('admin.layouts.app')

@section('title', 'Employees')
@section('page-subtitle', 'Every person employed across the group.')

@section('page-actions')
    @can('create', App\Models\Employee::class)
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-2"></i>Add employee
        </a>
    @endcan
@endsection

@section('content')

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.employees.index') }}"
           class="btn btn-sm {{ request('status') ? 'btn-outline-secondary' : 'btn-primary' }}">
            All <span class="badge bg-light text-dark ms-1">{{ $counts->sum() }}</span>
        </a>
        @foreach(['active', 'probation', 'on_leave', 'suspended', 'resigned', 'terminated'] as $status)
            <a href="{{ route('admin.employees.index', array_merge(request()->except('page'), ['status' => $status])) }}"
               class="btn btn-sm {{ request('status') === $status ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ ucwords(str_replace('_', ' ', $status)) }}
                <span class="badge bg-light text-dark ms-1">{{ $counts[$status] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Name, employee number, email or phone">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All departments</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" @selected(request('department') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="division" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All divisions</option>
                        @foreach($divisions as $id => $name)
                            <option value="{{ $id }}" @selected(request('division') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto ms-auto">
                    <span class="small text-muted">{{ $records->total() }} people</span>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Hired</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $employee->photo_url }}" alt="" class="thumb-sm rounded-circle"
                                         width="40" height="40">
                                    <div>
                                        <a href="{{ route('admin.employees.show', $employee) }}" class="fw-semibold">
                                            {{ $employee->full_name }}
                                        </a>
                                        <div class="small text-muted">
                                            {{ $employee->employee_number }}
                                            @if($employee->user_id)
                                                <i class="fa-solid fa-circle-check text-success ms-1"
                                                   data-bs-toggle="tooltip" title="Has a portal sign-in"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="small">{{ $employee->position?->title ?? '—' }}</td>
                            <td class="small text-muted">{{ $employee->date_hired?->format('j M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $employee->status_color }}-subtle text-{{ $employee->status_color }}">
                                    {{ $employee->status_label }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.employees.show', $employee) }}"
                                       class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="View profile">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @can('update', $employee)
                                        <a href="{{ route('admin.employees.edit', $employee) }}"
                                           class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $employee)
                                        <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}"
                                              data-confirm="Archive {{ $employee->full_name }} and disable their sign-in?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit"
                                                    data-bs-toggle="tooltip" title="Archive">
                                                <i class="fa-solid fa-box-archive"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-users"></i>
                                    <p class="mb-2">No employees match your filters.</p>
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

    {{ $records->links() }}

@endsection
