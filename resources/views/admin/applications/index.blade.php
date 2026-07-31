@extends('admin.layouts.app')

@section('title', 'Job Applications')
@section('page-subtitle', 'Candidates who applied through the careers portal.')

@section('content')

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.applications.index') }}"
           class="btn btn-sm {{ request('status') ? 'btn-outline-secondary' : 'btn-primary' }}">
            All <span class="badge bg-light text-dark ms-1">{{ $counts->sum() }}</span>
        </a>
        @foreach(\App\Models\JobApplication::STATUSES as $status)
            <a href="{{ route('admin.applications.index', array_merge(request()->except('page'), ['status' => $status])) }}"
               class="btn btn-sm {{ request('status') === $status ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ ucfirst($status) }}
                <span class="badge bg-light text-dark ms-1">{{ $counts[$status] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Name, email or reference">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="career" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All vacancies</option>
                        @foreach($vacancies as $id => $title)
                            <option value="{{ $id }}" @selected(request('career') == $id)>{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto ms-auto">
                    <span class="small text-muted">{{ $records->total() }} results</span>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Applied for</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th class="text-end">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $application)
                        <tr>
                            <td>
                                <a href="{{ route('admin.applications.show', $application) }}" class="fw-semibold">
                                    {{ $application->full_name }}
                                </a>
                                <div class="small text-muted">{{ $application->reference }}</div>
                            </td>
                            <td class="small">
                                {{ \Illuminate\Support\Str::limit($application->career?->title ?? 'General application', 44) }}
                            </td>
                            <td class="small">
                                {{ $application->years_experience !== null ? $application->years_experience.' yrs' : '—' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                                    {{ $application->status_label }}
                                </span>
                            </td>
                            <td class="text-end small">
                                <span data-bs-toggle="tooltip" title="{{ $application->created_at->format('j M Y, H:i') }}">
                                    {{ $application->created_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-user-tie"></i>
                                    <p class="mb-0">No applications match your filters.</p>
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
