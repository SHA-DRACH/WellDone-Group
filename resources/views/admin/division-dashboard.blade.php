@extends('admin.layouts.app')

@section('title', $division->name.' Dashboard')

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="{{ $division->icon }} text-primary"></i>
        {{ $division->name }}
    </span>
@endsection

{{--
    Guarded because the tagline is optional. Blade's two-argument @section
    decides which form it is by whether the second argument is null — pass
    null and it opens an output buffer waiting for an @endsection that this
    file does not have, swallowing the rest of the page.
--}}
@if(filled($division->tagline))
    @section('page-subtitle', $division->tagline)
@endif

@section('page-actions')
    @if($switchable->isNotEmpty())
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Switch division
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($switchable as $item)
                    <li>
                        <a class="dropdown-item {{ $item->id === $division->id ? 'active' : '' }}"
                           href="{{ route('admin.division.dashboard', $item->slug) }}">
                            <i class="{{ $item->icon }} me-2"></i>{{ $item->name }}
                        </a>
                    </li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                        <i class="fa-solid fa-gauge-high me-2"></i>Group dashboard
                    </a>
                </li>
            </ul>
        </div>
    @endif

    <a href="{{ route('site.divisions.show', $division->slug) }}" target="_blank" rel="noopener"
       class="btn btn-outline-primary">
        <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>View public page
    </a>
@endsection

@section('content')

    {{-- ---------------------------------------------- Headline figures --}}
    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => 'New requests',      'value' => $stats['requests_new'],      'icon' => 'fa-file-invoice-dollar', 'colour' => 'primary', 'route' => 'quotes.view'],
                ['label' => 'Open requests',     'value' => $stats['requests_open'],     'icon' => 'fa-hourglass-half',      'colour' => 'warning', 'route' => 'quotes.view'],
                ['label' => 'Won this period',   'value' => $stats['requests_won'],      'icon' => 'fa-circle-check',        'colour' => 'success', 'route' => 'quotes.view'],
                ['label' => 'Ongoing projects',  'value' => $stats['projects_ongoing'],  'icon' => 'fa-diagram-project',     'colour' => 'info',    'route' => 'projects.view'],
                ['label' => 'Active services',   'value' => $stats['services'],          'icon' => 'fa-list-check',          'colour' => 'secondary','route' => 'services.view'],
                ['label' => 'Open vacancies',    'value' => $stats['vacancies'],         'icon' => 'fa-briefcase',           'colour' => 'dark',    'route' => 'careers.view'],
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

    {{-- ---------------------------------------------- Operations log --}}
    @if($recordSchema)
        @include('admin.division-records._dashboard')
    @endif

    <div class="row g-4">
        {{-- ------------------------------------------ Requests / orders --}}
        <div class="col-xl-8">
            <div class="table-card mb-4">
                <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h6 mb-0">Requests &amp; orders</h2>
                        <small class="text-muted">
                            {{ $stats['requests_total'] }} all time ·
                            {{ $stats['requests_month'] }} this month
                            @if($stats['quoted_value'] > 0)
                                · @money($stats['quoted_value']) quoted
                            @endif
                        </small>
                    </div>

                    @can('quotes.view')
                        <a href="{{ route('admin.quotes.index', ['division' => $division->id]) }}"
                           class="btn btn-sm btn-outline-primary">Open the full queue</a>
                    @endcan
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Requester</th>
                                <th>Requirement</th>
                                <th>Status</th>
                                <th class="text-end">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                                <tr>
                                    <td>
                                        @can('quotes.view')
                                            <a href="{{ route('admin.quotes.show', $request) }}" class="fw-semibold">
                                                {{ $request->reference }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $request->reference }}</span>
                                        @endcan
                                        @if($request->assignee)
                                            <div class="small text-muted">{{ $request->assignee->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $request->contact_name }}</div>
                                        <div class="small text-muted">{{ $request->company_name ?: $request->email }}</div>
                                    </td>
                                    <td>
                                        <div>{{ \Illuminate\Support\Str::limit($request->project_title, 44) }}</div>
                                        @if($request->services->isNotEmpty())
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::limit($request->services->pluck('name')->implode(', '), 52) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->status_color }}-subtle text-{{ $request->status_color }}">
                                            {{ $request->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span data-bs-toggle="tooltip" title="{{ $request->created_at->format('j M Y, H:i') }}">
                                            {{ $request->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa-regular fa-folder-open"></i>
                                            <p class="mb-0">No requests have come in for this division yet.</p>
                                            <p class="small mb-0">
                                                They arrive from the public
                                                <a href="{{ route('site.quote.create') }}" target="_blank" rel="noopener">Request a Quote</a>
                                                form.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- --------------------------------------- Active projects --}}
            <div class="table-card">
                <div class="p-3 border-bottom">
                    <h2 class="h6 mb-0">Active &amp; upcoming projects</h2>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Location</th>
                                <th style="width: 180px;">Progress</th>
                                <th class="text-end">Target end</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($project->title, 50) }}</div>
                                        <span class="badge bg-{{ $project->status === 'ongoing' ? 'primary' : 'secondary' }}-subtle
                                                     text-{{ $project->status === 'ongoing' ? 'primary' : 'secondary' }}">
                                            {{ $project->status_label }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $project->location ?: '—' }}</td>
                                    <td>
                                        <div class="progress" style="height: 7px;" role="progressbar"
                                             aria-valuenow="{{ $project->completion_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar" style="width: {{ $project->completion_percentage }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $project->completion_percentage }}%</small>
                                    </td>
                                    <td class="text-end small">{{ $project->end_date?->format('j M Y') ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-diagram-project"></i>
                                            <p class="mb-0">No ongoing or upcoming projects recorded.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------ Side column --}}
        <div class="col-xl-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">Request pipeline</h2>

                @php($totalRequests = max(1, $statusBreakdown->sum()))
                @forelse(\App\Models\QuoteRequest::STATUSES as $status)
                    @php($count = $statusBreakdown[$status] ?? 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                        <div class="progress" style="height: 6px;" role="progressbar"
                             aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ $totalRequests }}">
                            <div class="progress-bar" style="width: {{ round($count / $totalRequests * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No requests yet.</p>
                @endforelse
            </div>

            @if($topServices->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 mb-3">Most-requested services</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach($topServices as $service)
                            <li class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                                <span class="small">
                                    <i class="{{ $service->icon }} text-primary me-2"></i>{{ $service->name }}
                                </span>
                                <span class="badge bg-light text-muted">{{ $service->quote_requests_count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-soft p-4 mb-4">
                <h2 class="h6 mb-3">Recruitment</h2>
                <dl class="row small mb-3">
                    <dt class="col-8 fw-normal text-muted">Open vacancies</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['vacancies'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">New applications</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['applications_new'] }}</dd>
                    <dt class="col-8 fw-normal text-muted">People in division</dt>
                    <dd class="col-4 text-end fw-semibold">{{ $stats['team'] }}</dd>
                </dl>

                @forelse($applications as $application)
                    <div class="d-flex justify-content-between align-items-start {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                        <div>
                            <div class="small fw-semibold">{{ $application->full_name }}</div>
                            <div class="text-muted" style="font-size:.76rem;">
                                {{ \Illuminate\Support\Str::limit($application->career?->title ?? 'General', 34) }}
                            </div>
                        </div>
                        <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                            {{ $application->status_label }}
                        </span>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No applications yet.</p>
                @endforelse
            </div>

            <div class="card-soft p-4">
                <h2 class="h6 mb-3">Quick links</h2>
                {{--
                    Route::has() guards each link: the admin modules are being
                    rolled out incrementally, and a link to a module that is
                    not yet mounted would fail the whole page render.
                --}}
                <div class="d-grid gap-2">
                    @foreach($quickLinks as $link)
                        @can($link['can'])
                            <a href="{{ route($link['route'], ['division' => $division->id]) }}"
                               class="btn btn-sm btn-outline-primary text-start">
                                <i class="fa-solid {{ $link['icon'] }} me-2"></i>{{ $link['label'] }}
                            </a>
                        @endcan
                    @endforeach

                    <a href="{{ route('site.divisions.show', $division->slug) }}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-secondary text-start">
                        <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Public division page
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
