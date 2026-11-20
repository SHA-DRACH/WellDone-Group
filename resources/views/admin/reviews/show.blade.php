@extends('admin.layouts.app')

@section('title', $review->title.' — Document Review')

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-file-signature text-primary"></i>{{ $review->title }}
    </span>
@endsection

@section('page-subtitle', $review->reference.' · '.($review->division?->name ?? 'No division'))

@section('page-actions')
    <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>All documents
    </a>
    <a href="{{ route('admin.reviews.file', ['review' => $review, 'download' => 1]) }}" class="btn btn-outline-primary">
        <i class="fa-solid fa-download me-2"></i>Download
    </a>
@endsection

@section('content')
    @php($ext = strtolower(pathinfo($review->original_name, PATHINFO_EXTENSION)))

    <div class="row g-4">
        {{-- ------------------------------------------ The document --}}
        <div class="col-xl-8">
            <div class="table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-2">
                    <div class="small text-truncate">
                        <i class="fa-solid fa-paperclip me-1 text-muted"></i>{{ $review->original_name }}
                        <span class="text-muted">· {{ \App\Support\Format::fileSize((int) $review->file_size) }}</span>
                    </div>
                    <a href="{{ route('admin.reviews.file', $review) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-up-right-from-square me-1"></i>Open in new tab
                    </a>
                </div>

                @if($ext === 'pdf')
                    <iframe src="{{ route('admin.reviews.file', $review) }}" title="{{ $review->title }}"
                            style="width:100%; height:78vh; border:0; display:block;"></iframe>
                @elseif(in_array($ext, ['jpg', 'jpeg', 'png'], true))
                    <div class="p-3 text-center bg-light">
                        <img src="{{ route('admin.reviews.file', $review) }}" alt="{{ $review->title }}" class="img-fluid rounded border">
                    </div>
                @else
                    <div class="p-5 text-center">
                        <i class="fa-solid fa-file-lines fa-3x text-muted mb-3"></i>
                        <p class="mb-3">This {{ strtoupper($ext) }} file cannot be shown in the browser.</p>
                        <a href="{{ route('admin.reviews.file', ['review' => $review, 'download' => 1]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-download me-2"></i>Download to view
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------ Details & decision --}}
        <div class="col-xl-4">
            <div class="card-soft p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h2 class="h6 mb-0">Details</h2>
                    <span class="badge bg-{{ $review->status_colour }}-subtle text-{{ $review->status_colour }} fs-6">{{ $review->status_label }}</span>
                </div>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Type</dt><dd class="col-7">{{ $review->category_label }}</dd>
                    <dt class="col-5 text-muted fw-normal">Division</dt><dd class="col-7">{{ $review->division?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Submitted by</dt>
                    <dd class="col-7">{{ $review->submitter?->name ?? '—' }}@if($review->submitter?->position)<div class="text-muted">{{ $review->submitter->position }}</div>@endif</dd>
                    <dt class="col-5 text-muted fw-normal">Submitted</dt><dd class="col-7">{{ $review->created_at->format('d M Y, H:i') }}</dd>
                </dl>

                @if($review->notes)
                    <hr>
                    <div class="small text-muted mb-1">Note from the division</div>
                    <div class="small" style="white-space: pre-line;">{{ $review->notes }}</div>
                @endif
            </div>

            @unless($review->isPending())
                <div class="card-soft p-4 mb-4 border-start border-4 border-{{ $review->status_colour }}">
                    <h2 class="h6 mb-2">Decision</h2>
                    <p class="small mb-1"><strong>{{ $review->status_label }}</strong> by {{ $review->reviewer?->name ?? '—' }}</p>
                    <p class="small text-muted mb-2">{{ $review->reviewed_at?->format('d M Y, H:i') }}</p>
                    @if($review->review_comment)
                        <div class="small" style="white-space: pre-line;">{{ $review->review_comment }}</div>
                    @endif
                </div>
            @endunless

            @can('review', $review)
                <div class="card-soft p-4">
                    <h2 class="h6 mb-3">{{ $review->isPending() ? 'Your decision' : 'Change the decision' }}</h2>

                    <form method="POST" action="{{ route('admin.reviews.decide', $review) }}" novalidate>
                        @csrf

                        @error('decision')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

                        @php($current = old('decision', $review->isPending() ? 'approved' : $review->status))
                        @foreach([
                            'approved' => ['Approve', 'success', 'fa-circle-check'],
                            'changes_requested' => ['Ask for changes', 'warning', 'fa-pen-to-square'],
                            'rejected' => ['Reject', 'danger', 'fa-circle-xmark'],
                        ] as $value => [$label, $colour, $icon])
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="decision" id="decision-{{ $value }}" value="{{ $value }}" @checked($current === $value)>
                                <label class="form-check-label" for="decision-{{ $value }}">
                                    <i class="fa-solid {{ $icon }} text-{{ $colour }} me-1"></i>{{ $label }}
                                </label>
                            </div>
                        @endforeach

                        <div class="mt-3 mb-3">
                            <label class="form-label small mb-1" for="review-comment">Comment</label>
                            <textarea id="review-comment" name="review_comment" rows="4" maxlength="3000"
                                      class="form-control form-control-sm @error('review_comment') is-invalid @enderror"
                                      placeholder="Required when asking for changes or rejecting.">{{ old('review_comment', $review->review_comment) }}</textarea>
                            @error('review_comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="fa-solid fa-gavel me-2"></i>Record decision
                        </button>
                        <div class="form-text text-center">{{ $review->submitter?->name ?? 'The submitter' }} is emailed your decision.</div>
                    </form>
                </div>
            @endcan
        </div>
    </div>
@endsection
