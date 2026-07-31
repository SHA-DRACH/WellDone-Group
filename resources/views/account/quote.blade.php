@extends('account.layout')

@section('account-content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">{{ $quote->project_title }}</h2>
        <a href="{{ route('account.quotes') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <div class="card-soft p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 pb-3 border-bottom">
            <div>
                <div class="small text-muted">Reference</div>
                <div class="h6 mb-0 text-primary">{{ $quote->reference }}</div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Status</div>
                <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                    {{ $quote->status_label }}
                </span>
            </div>
        </div>

        <h3 class="h6 text-uppercase text-muted mb-2" style="letter-spacing:.1em;">What you asked for</h3>
        <p>{!! nl2br(e($quote->description)) !!}</p>

        <dl class="row small mt-4 mb-0">
            <dt class="col-sm-4 text-muted fw-normal">Division</dt>
            <dd class="col-sm-8">{{ $quote->division?->name ?? 'Not specified' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Services requested</dt>
            <dd class="col-sm-8">
                @forelse($quote->services as $service)
                    <span class="category-chip">{{ $service->name }}</span>
                @empty
                    Not specified
                @endforelse
            </dd>

            <dt class="col-sm-4 text-muted fw-normal">Budget</dt>
            <dd class="col-sm-8">{{ $quote->budget_range ?: 'Not stated' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Timeline</dt>
            <dd class="col-sm-8">{{ $quote->timeline ?: 'Not stated' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Delivery location</dt>
            <dd class="col-sm-8">{{ $quote->delivery_location ?: 'Not stated' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Sent</dt>
            <dd class="col-sm-8">{{ $quote->created_at->format('j F Y, H:i') }}</dd>

            @if($quote->quoted_amount)
                <dt class="col-sm-4 text-muted fw-normal">Quoted</dt>
                <dd class="col-sm-8 fw-semibold">
                    @money($quote->quoted_amount, $quote->currency)
                    @if($quote->quoted_at)
                        <span class="text-muted fw-normal">on {{ $quote->quoted_at->format('j M Y') }}</span>
                    @endif
                </dd>
            @endif
        </dl>

        @if($quote->files->isNotEmpty())
            <h3 class="h6 text-uppercase text-muted mt-4 mb-2" style="letter-spacing:.1em;">
                Documents you attached
            </h3>
            <ul class="list-unstyled mb-0 small">
                @foreach($quote->files as $file)
                    <li class="mb-1">
                        <i class="fa-solid fa-paperclip text-muted me-2"></i>{{ $file->original_name }}
                        <span class="text-muted">({{ $file->human_size }})</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card-soft p-4">
        <h3 class="h6 mb-2">Need to change something?</h3>
        <p class="small text-muted mb-3">
            Quote your reference <strong>{{ $quote->reference }}</strong> and our team will pick it up.
        </p>
        <a href="{{ route('site.contact') }}" class="btn btn-sm btn-outline-primary">Contact us about this request</a>
    </div>

@endsection
