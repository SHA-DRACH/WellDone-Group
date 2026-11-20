{{--
    Division dashboard: send a document up to the CEO / Managing Director,
    and follow what happened to recent submissions.

    Expects: $division, $recentReviews.
--}}
@php($reviewErrors = $errors->getBag('review'))

<div class="quick-entry mb-4" id="document-review">
    <div class="quick-entry__head">
        <div>
            <h2 class="h6 mb-0"><i class="fa-solid fa-file-signature text-primary me-2"></i>Documents for review</h2>
            <small class="text-muted">Send a report, contract or request to the CEO or Managing Director for approval.</small>
        </div>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-list me-1"></i>All submissions
        </a>
    </div>

    <div class="quick-entry__body">
        <div class="row g-4">
            @can('create', App\Models\DocumentReview::class)
                <div class="col-lg-6">
                    <form method="POST" action="{{ route('admin.division.reviews.store', $division->slug) }}"
                          enctype="multipart/form-data" class="border rounded-3 p-3 bg-white h-100" novalidate>
                        @csrf
                        <h3 class="h6 mb-3">Submit a document</h3>

                        @if($reviewErrors->any())
                            <div class="alert alert-danger small py-2">
                                @foreach($reviewErrors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="doc-title">Title <span class="required-mark">*</span></label>
                            <input type="text" id="doc-title" name="doc_title" value="{{ old('doc_title') }}" maxlength="200" required
                                   class="form-control form-control-sm {{ $reviewErrors->has('doc_title') ? 'is-invalid' : '' }}"
                                   placeholder="e.g. October operations report">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="doc-category">Type</label>
                            <select id="doc-category" name="doc_category" class="form-select form-select-sm">
                                @foreach(App\Models\DocumentReview::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('doc_category', 'report') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1" for="doc-notes">Note for the reviewer</label>
                            <textarea id="doc-notes" name="doc_notes" rows="3" maxlength="3000"
                                      class="form-control form-control-sm {{ $reviewErrors->has('doc_notes') ? 'is-invalid' : '' }}"
                                      placeholder="What needs approving, and by when?">{{ old('doc_notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1" for="doc-file">Document <span class="required-mark">*</span></label>
                            <input type="file" id="doc-file" name="doc_file" required
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                   class="form-control form-control-sm {{ $reviewErrors->has('doc_file') ? 'is-invalid' : '' }}">
                            <div class="form-text">PDF, Word, Excel or image · up to 20 MB</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-paper-plane me-1"></i>Send for review
                        </button>
                    </form>
                </div>
            @endcan

            <div class="col-lg-{{ auth()->user()->can('create', App\Models\DocumentReview::class) ? '6' : '12' }}">
                <h3 class="h6 mb-2">Recent submissions</h3>
                @forelse($recentReviews as $review)
                    <a href="{{ route('admin.reviews.show', $review) }}"
                       class="d-flex justify-content-between align-items-start gap-2 border rounded-3 p-2 mb-2 bg-white text-decoration-none text-reset">
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold small text-truncate">{{ $review->title }}</div>
                            <div class="text-muted" style="font-size:.78rem;">
                                {{ $review->reference }} · {{ $review->created_at->format('d M Y') }}
                                @if($review->submitter) · {{ $review->submitter->name }} @endif
                            </div>
                            @if($review->review_comment && ! $review->isPending())
                                <div class="small text-muted fst-italic text-truncate">“{{ $review->review_comment }}”</div>
                            @endif
                        </div>
                        <span class="badge bg-{{ $review->status_colour }}-subtle text-{{ $review->status_colour }} flex-shrink-0">{{ $review->status_label }}</span>
                    </a>
                @empty
                    <p class="small text-muted mb-0">Nothing submitted yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
