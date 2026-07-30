@extends('admin.layouts.app')

@section('title', 'Quote Requests')
@section('page-subtitle', 'Every request submitted through the public quotation form.')

@section('content')

    {{-- Status filter strip --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.quotes.index') }}"
           class="btn btn-sm {{ request('status') ? 'btn-outline-secondary' : 'btn-primary' }}">
            All <span class="badge bg-light text-dark ms-1">{{ $counts->sum() }}</span>
        </a>
        @foreach(\App\Models\QuoteRequest::STATUSES as $status)
            <a href="{{ route('admin.quotes.index', array_merge(request()->except('page'), ['status' => $status])) }}"
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

                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Reference, name, company, email or project">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="division" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All divisions</option>
                        @foreach($divisions as $id => $name)
                            <option value="{{ $id }}" @selected(request('division') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="assigned" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Anyone</option>
                        <option value="me" @selected(request('assigned') === 'me')>Assigned to me</option>
                        <option value="none" @selected(request('assigned') === 'none')>Unassigned</option>
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
                        <th>Reference</th>
                        <th>Requester</th>
                        <th>Requirement</th>
                        <th>Division</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th class="text-end">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $quote)
                        <tr>
                            <td>
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="fw-semibold">{{ $quote->reference }}</a>
                                <div class="small">
                                    <span class="badge bg-{{ $quote->priority_color }}-subtle text-{{ $quote->priority_color }}">
                                        {{ ucfirst($quote->priority) }}
                                    </span>
                                    @if($quote->files_count)
                                        <span class="text-muted ms-1">
                                            <i class="fa-solid fa-paperclip"></i> {{ $quote->files_count }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $quote->contact_name }}</div>
                                <div class="small text-muted">{{ $quote->company_name ?: $quote->email }}</div>
                            </td>
                            <td>
                                <div>{{ \Illuminate\Support\Str::limit($quote->project_title, 46) }}</div>
                                @if($quote->budget_range)
                                    <div class="small text-muted">Budget: {{ $quote->budget_range }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $quote->division?->name ?? '—' }}</td>
                            <td class="small">{{ $quote->assignee?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                                    {{ $quote->status_label }}
                                </span>
                            </td>
                            <td class="text-end small">
                                <span data-bs-toggle="tooltip" title="{{ $quote->created_at->format('j M Y, H:i') }}">
                                    {{ $quote->created_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                    <p class="mb-0">No quotation requests match your filters.</p>
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
