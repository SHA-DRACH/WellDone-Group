@extends('account.layout')

@section('account-content')

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Requests sent',   'value' => $counts['quotes'],       'icon' => 'fa-file-invoice-dollar', 'colour' => 'primary'],
            ['label' => 'Still open',      'value' => $counts['quotes_open'],  'icon' => 'fa-hourglass-half',      'colour' => 'warning'],
            ['label' => 'Applications',    'value' => $counts['applications'], 'icon' => 'fa-briefcase',           'colour' => 'success'],
            ['label' => 'Enquiries',       'value' => $counts['messages'],     'icon' => 'fa-envelope',            'colour' => 'info'],
        ] as $tile)
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <span class="stat-tile__icon bg-{{ $tile['colour'] }}-subtle text-{{ $tile['colour'] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="stat-tile__value">{{ $tile['value'] }}</div>
                        <div class="stat-tile__label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ------------------------------------------ Recent requests --}}
    <div class="card-soft p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Recent quotation requests</h2>
            @if($quotes->isNotEmpty())
                <a href="{{ route('account.quotes') }}" class="btn btn-sm btn-outline-primary">View all</a>
            @endif
        </div>

        @forelse($quotes as $quote)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div>
                    <a href="{{ route('account.quotes.show', $quote) }}" class="fw-semibold text-decoration-none">
                        {{ $quote->project_title }}
                    </a>
                    <div class="small text-muted">
                        {{ $quote->reference }}
                        @if($quote->division) · {{ $quote->division->name }} @endif
                        · {{ $quote->created_at->format('j M Y') }}
                    </div>
                </div>
                <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                    {{ $quote->status_label }}
                </span>
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <p class="mb-2">You have not sent a quotation request yet.</p>
                <a href="{{ route('site.quote.create') }}" class="btn btn-sm btn-primary">Request a quote</a>
            </div>
        @endforelse
    </div>

    {{-- ------------------------------------------ Applications --}}
    <div class="card-soft p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">My job applications</h2>
            @if($applications->isNotEmpty())
                <a href="{{ route('account.applications') }}" class="btn btn-sm btn-outline-primary">View all</a>
            @endif
        </div>

        @forelse($applications as $application)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div>
                    <span class="fw-semibold">{{ $application->career?->title ?? 'General application' }}</span>
                    <div class="small text-muted">
                        {{ $application->reference }} · applied {{ $application->created_at->format('j M Y') }}
                    </div>
                </div>
                <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                    {{ $application->status_label }}
                </span>
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-briefcase"></i>
                <p class="mb-2">You have not applied for a vacancy yet.</p>
                <a href="{{ route('site.careers.index') }}" class="btn btn-sm btn-primary">Browse open roles</a>
            </div>
        @endforelse
    </div>

    {{-- ------------------------------------------ Open vacancies --}}
    @if($openVacancies->isNotEmpty())
        <div class="card-soft p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Open vacancies</h2>
                <a href="{{ route('site.careers.index') }}" class="btn btn-sm btn-outline-primary">All vacancies</a>
            </div>

            @foreach($openVacancies as $vacancy)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                    <div>
                        <a href="{{ $vacancy->url }}" class="fw-semibold text-decoration-none">{{ $vacancy->title }}</a>
                        <div class="small text-muted">
                            {{ collect([$vacancy->location, $vacancy->employment_type_label])->filter()->implode(' · ') }}
                        </div>
                    </div>
                    @if($vacancy->closes_at)
                        <span class="small text-muted">Closes {{ $vacancy->closes_at->format('j M') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

@endsection
