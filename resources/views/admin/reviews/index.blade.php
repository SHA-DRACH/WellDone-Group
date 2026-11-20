@extends('admin.layouts.app')

@section('title', 'Document Reviews')

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-file-signature text-primary"></i>Document Reviews
    </span>
@endsection

@section('page-subtitle', $isReviewer
    ? 'Documents the divisions have sent for your approval.'
    : 'Documents your division has sent to the CEO / Managing Director, and their decisions.')

@section('content')

    {{-- Status counts --}}
    <div class="row g-3 mb-4">
        @foreach(App\Models\DocumentReview::STATUSES as $s)
            @php($probe = new App\Models\DocumentReview(['status' => $s]))
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.reviews.index', ['status' => $s]) }}" class="text-decoration-none">
                    <div class="metric-card {{ $status === $s ? 'metric-card--accent' : '' }}">
                        <div class="metric-card__label">{{ $probe->status_label }}</div>
                        <div class="metric-card__value text-{{ $probe->status_colour }}">{{ number_format($counts[$s] ?? 0) }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="table-card">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1" for="f-q">Search</label>
                    <input type="search" id="f-q" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Title or reference">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1" for="f-status">Status</label>
                    <select id="f-status" name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(App\Models\DocumentReview::STATUSES as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ (new App\Models\DocumentReview(['status' => $s]))->status_label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($divisions->count() > 1)
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1" for="f-division">Division</label>
                        <select id="f-division" name="division" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($divisions as $id => $name)
                                <option value="{{ $id }}" @selected((int) request('division') === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear">✕</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Division</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>
                                <a href="{{ route('admin.reviews.show', $review) }}" class="fw-semibold text-decoration-none">{{ $review->title }}</a>
                                <div class="small text-muted">{{ $review->reference }} · {{ $review->category_label }}</div>
                            </td>
                            <td class="small">{{ $review->division?->name ?? '—' }}</td>
                            <td class="small">
                                {{ $review->created_at->format('d M Y, H:i') }}
                                <div class="text-muted">{{ $review->submitter?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $review->status_colour }}-subtle text-{{ $review->status_colour }}">{{ $review->status_label }}</span>
                                @if($review->reviewer && ! $review->isPending())
                                    <div class="small text-muted">by {{ $review->reviewer->name }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.reviews.file', $review) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Open document">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-sm {{ $isReviewer && $review->isPending() ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $isReviewer && $review->isPending() ? 'Review' : 'Details' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                No documents {{ $status ? 'with this status' : 'submitted yet' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="p-3 border-top">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
