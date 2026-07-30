@extends('admin.layouts.app')

@section('title', 'Group Dashboard')
@section('page-subtitle', 'Activity across all four divisions.')

@section('content')

    {{-- ---------------------------------------------- Headline tiles --}}
    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => 'New requests',     'value' => $stats['quotes_new'],       'icon' => 'fa-file-invoice-dollar', 'colour' => 'primary'],
                ['label' => 'Open requests',    'value' => $stats['quotes_open'],      'icon' => 'fa-hourglass-half',      'colour' => 'warning'],
                ['label' => 'New messages',     'value' => $stats['messages_new'],     'icon' => 'fa-envelope',            'colour' => 'info'],
                ['label' => 'New applications', 'value' => $stats['applications_new'], 'icon' => 'fa-user-tie',            'colour' => 'success'],
                ['label' => 'Active projects',  'value' => $stats['projects'],         'icon' => 'fa-diagram-project',     'colour' => 'secondary'],
                ['label' => 'Subscribers',      'value' => $stats['subscribers'],      'icon' => 'fa-paper-plane',         'colour' => 'dark'],
            ];
        @endphp

        @foreach($tiles as $tile)
            <div class="col-6 col-lg-4 col-xl-2">
                <div class="stat-tile h-100">
                    <span class="stat-tile__icon bg-{{ $tile['colour'] }}-subtle text-{{ $tile['colour'] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="stat-tile__value">{{ number_format($tile['value']) }}</div>
                        <div class="stat-tile__label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- ------------------------------------------ Traffic --}}
        <div class="col-xl-8">
            <div class="card-soft p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h6 mb-1">Website traffic</h2>
                        <small class="text-muted">Last 30 days · first-party counter</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold h5 mb-0">{{ number_format($traffic['views_total']) }}</div>
                        <small class="text-muted">{{ number_format($traffic['visitors_total']) }} unique visitors</small>
                    </div>
                </div>

                @if($traffic['views_total'] > 0)
                    @php
                        $points = collect($traffic['series'])
                            ->values()
                            ->map(fn ($row, $i) => round($i * (700 / max(1, count($traffic['series']) - 1)), 1)
                                .','.round(120 - ($row['views'] / $traffic['peak'] * 100), 1))
                            ->implode(' ');
                    @endphp

                    <svg viewBox="0 0 700 130" width="100%" height="130" role="img"
                         aria-label="Page views over the last 30 days" preserveAspectRatio="none">
                        <polyline points="{{ $points }}" fill="none" stroke="#0b3c91" stroke-width="2.5"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="0,120 {{ $points }} 700,120" fill="rgba(11,60,145,.10)" stroke="none"/>
                    </svg>

                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>{{ $traffic['series'][0]['date']->format('j M') }}</span>
                        <span>{{ end($traffic['series'])['date']->format('j M') }}</span>
                    </div>
                @else
                    <div class="empty-state py-4">
                        <i class="fa-solid fa-chart-line"></i>
                        <p class="mb-0">No traffic recorded yet — figures appear as visitors browse the site.</p>
                    </div>
                @endif
            </div>

            {{-- ------------------------------------------ Recent requests --}}
            @if($recentQuotes->isNotEmpty())
                <div class="table-card mb-4">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">Latest quotation requests</h2>
                        <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Requester</th>
                                    <th>Division</th>
                                    <th>Status</th>
                                    <th class="text-end">Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentQuotes as $quote)
                                    <tr>
                                        <td><a href="{{ route('admin.quotes.show', $quote) }}" class="fw-semibold">{{ $quote->reference }}</a></td>
                                        <td>
                                            <div>{{ $quote->contact_name }}</div>
                                            <div class="small text-muted">{{ $quote->company_name ?: $quote->email }}</div>
                                        </td>
                                        <td class="small">{{ $quote->division?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                                                {{ $quote->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-end small">{{ $quote->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ------------------------------------------ Messages & applications --}}
            <div class="row g-4">
                @if($recentMessages->isNotEmpty())
                    <div class="col-md-6">
                        <div class="card-soft p-4 h-100">
                            <h2 class="h6 mb-3">Recent enquiries</h2>
                            @foreach($recentMessages as $message)
                                <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="small">{{ $message->name }}</strong>
                                        <span class="badge bg-{{ $message->status_color }}-subtle text-{{ $message->status_color }}">
                                            {{ ucfirst($message->status) }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($message->subject, 52) }}</div>
                                    <div class="text-muted" style="font-size:.74rem;">{{ $message->created_at->diffForHumans() }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($recentApplications->isNotEmpty())
                    <div class="col-md-6">
                        <div class="card-soft p-4 h-100">
                            <h2 class="h6 mb-3">Recent applications</h2>
                            @foreach($recentApplications as $application)
                                <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="small">{{ $application->full_name }}</strong>
                                        <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                                            {{ $application->status_label }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ \Illuminate\Support\Str::limit($application->career?->title ?? 'General', 46) }}
                                    </div>
                                    <div class="text-muted" style="font-size:.74rem;">{{ $application->created_at->diffForHumans() }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ------------------------------------------ Side column --}}
        <div class="col-xl-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">Division dashboards</h2>
                <div class="d-grid gap-2">
                    @foreach(\App\Models\Division::query()->active()->ordered()->get() as $division)
                        <a href="{{ route('admin.division.dashboard', $division->slug) }}"
                           class="btn btn-sm btn-outline-primary text-start">
                            <i class="{{ $division->icon }} me-2"></i>{{ $division->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">Content at a glance</h2>
                <dl class="row small mb-0">
                    <dt class="col-8 fw-normal text-muted">Published articles</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['posts_published'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">Draft articles</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['posts_draft'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">Active services</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['services'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">Ongoing projects</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['projects_ongoing'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">Open vacancies</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['careers_open'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">Active staff accounts</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['users'] }}</dd>
                </dl>
            </div>

            @if($traffic['top_pages']->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-3">Most-visited pages</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach($traffic['top_pages'] as $page)
                            <li class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                                <span class="small text-truncate" style="max-width: 70%;">
                                    /{{ $page->path === '/' ? '' : $page->path }}
                                </span>
                                <span class="badge bg-light text-muted">{{ number_format($page->hits) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($activity->isNotEmpty())
                <div class="card-soft p-4">
                    <h2 class="h6 mb-3">Recent changes</h2>
                    @foreach($activity as $entry)
                        <div class="{{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <div class="small">
                                <strong>{{ $entry->causer?->name ?? 'System' }}</strong>
                                {{ $entry->description }}
                                <span class="text-muted">{{ $entry->log_name }}</span>
                            </div>
                            <div class="text-muted" style="font-size:.74rem;">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

@endsection
