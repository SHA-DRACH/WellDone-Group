@extends('account.layout')

@section('account-content')

    <div class="card-soft p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">My quotation requests</h2>
            <a href="{{ route('site.quote.create') }}" class="btn btn-sm btn-primary">New request</a>
        </div>

        @forelse($quotes as $quote)
            <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <a href="{{ route('account.quotes.show', $quote) }}" class="fw-semibold text-decoration-none">
                            {{ $quote->project_title }}
                        </a>
                        <div class="small text-muted">
                            {{ $quote->reference }} · sent {{ $quote->created_at->format('j F Y') }}
                            @if($quote->files_count)
                                · {{ $quote->files_count }} {{ \Illuminate\Support\Str::plural('attachment', $quote->files_count) }}
                            @endif
                        </div>
                        @if($quote->services->isNotEmpty())
                            <div class="mt-2">
                                @foreach($quote->services as $service)
                                    <span class="category-chip">{{ $service->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="text-end">
                        <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                            {{ $quote->status_label }}
                        </span>
                        @if($quote->quoted_amount)
                            <div class="small fw-semibold mt-1">
                                @money($quote->quoted_amount, $quote->currency)
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <p class="mb-2">You have not sent a quotation request yet.</p>
                <a href="{{ route('site.quote.create') }}" class="btn btn-sm btn-primary">Request a quote</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $quotes->links() }}</div>
    </div>

@endsection
