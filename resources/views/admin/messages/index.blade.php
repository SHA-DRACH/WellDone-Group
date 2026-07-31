@extends('admin.layouts.app')

@section('title', 'Contact Messages')
@section('page-subtitle', 'Enquiries submitted through the website contact form.')

@section('content')

    @isset($scopedDivision)
        @if($scopedDivision)
            <div class="alert alert-info small d-flex gap-2">
                <i class="fa-solid fa-circle-info mt-1"></i>
                <div>
                    You are seeing enquiries routed to <strong>{{ $scopedDivision->name }}</strong> only.
                    General enquiries and those for other divisions are handled by the group team.
                </div>
            </div>
        @endif
    @endisset

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.messages.index') }}"
           class="btn btn-sm {{ request('status') ? 'btn-outline-secondary' : 'btn-primary' }}">
            All <span class="badge bg-light text-dark ms-1">{{ $counts->sum() }}</span>
        </a>
        @foreach(\App\Models\ContactMessage::STATUSES as $status)
            <a href="{{ route('admin.messages.index', array_merge(request()->except('page'), ['status' => $status])) }}"
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
                               placeholder="Name, email or subject">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="department" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All departments</option>
                        @foreach(['general' => 'General', 'sales' => 'Sales', 'careers' => 'Careers', 'support' => 'Support', 'accounts' => 'Accounts'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('department') === $key)>{{ $label }}</option>
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
                        <th>From</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $message)
                        <tr class="{{ $message->status === 'new' ? 'fw-semibold' : '' }}">
                            <td>
                                <a href="{{ route('admin.messages.show', $message) }}">{{ $message->name }}</a>
                                <div class="small text-muted fw-normal">{{ $message->company ?: $message->email }}</div>
                            </td>
                            <td>
                                <div>{{ \Illuminate\Support\Str::limit($message->subject, 52) }}</div>
                                <div class="small text-muted fw-normal">
                                    {{ \Illuminate\Support\Str::limit($message->message, 70) }}
                                </div>
                            </td>
                            <td class="small fw-normal">{{ ucfirst($message->department ?: 'general') }}</td>
                            <td>
                                <span class="badge bg-{{ $message->status_color }}-subtle text-{{ $message->status_color }}">
                                    {{ ucfirst($message->status) }}
                                </span>
                            </td>
                            <td class="text-end small fw-normal">
                                <span data-bs-toggle="tooltip" title="{{ $message->created_at->format('j M Y, H:i') }}">
                                    {{ $message->created_at->diffForHumans() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-envelope-open"></i>
                                    <p class="mb-0">No enquiries match your filters.</p>
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
