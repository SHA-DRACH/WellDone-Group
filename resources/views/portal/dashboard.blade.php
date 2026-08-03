@extends('portal.layout')

@section('portal-content')

    <div class="card-soft p-4 mb-4">
        <h2 class="h5 mb-1">Welcome back, {{ $employee->first_name }}.</h2>
        <p class="text-muted mb-0">
            {{ $employee->position?->title ?? 'Employee' }}
            @if($employee->department) · {{ $employee->department->name }} @endif
            @if($employee->years_of_service !== null)
                · {{ $employee->years_of_service }} {{ \Illuminate\Support\Str::plural('year', $employee->years_of_service) }} of service
            @endif
        </p>
    </div>

    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => 'Employee number', 'value' => $employee->employee_number, 'icon' => 'fa-id-badge', 'colour' => 'primary'],
                ['label' => 'Department',      'value' => $employee->department?->name ?? '—', 'icon' => 'fa-sitemap', 'colour' => 'info'],
                ['label' => 'Reports to',      'value' => $employee->manager?->full_name ?? '—', 'icon' => 'fa-user-tie', 'colour' => 'secondary'],
                ['label' => 'Date hired',      'value' => $employee->date_hired?->format('j M Y') ?? '—', 'icon' => 'fa-calendar-check', 'colour' => 'success'],
            ];
        @endphp

        @foreach($tiles as $tile)
            <div class="col-sm-6 col-xl-3">
                <div class="stat-tile h-100">
                    <span class="stat-tile__icon bg-{{ $tile['colour'] }}-subtle text-{{ $tile['colour'] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="fw-semibold" style="font-size:.95rem; line-height:1.3;">{{ $tile['value'] }}</div>
                        <div class="stat-tile__label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ------------------------------------------ Announcements --}}
    <div class="card-soft p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Latest announcements</h2>
            @if($announcements->isNotEmpty())
                <a href="{{ route('portal.announcements') }}" class="btn btn-sm btn-outline-primary">View all</a>
            @endif
        </div>

        @forelse($announcements as $announcement)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div>
                    <a href="{{ route('portal.announcements.show', $announcement->slug) }}"
                       class="fw-semibold text-decoration-none">
                        @if($announcement->is_pinned)
                            <i class="fa-solid fa-thumbtack text-warning me-1" title="Pinned"></i>
                        @endif
                        {{ $announcement->title }}
                    </a>
                    <div class="small text-muted">
                        {{ $announcement->published_at?->format('j M Y') }}
                        · {{ $announcement->audience }}
                    </div>
                </div>
                <span class="badge bg-{{ $announcement->category_color }}-subtle text-{{ $announcement->category_color }}">
                    {{ ucfirst($announcement->category) }}
                </span>
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-bullhorn"></i>
                <p class="mb-0">No announcements at the moment.</p>
            </div>
        @endforelse
    </div>

    {{-- ------------------------------------------ Team --}}
    <div class="row g-4">
        @if($directReports->isNotEmpty())
            <div class="col-md-6">
                <div class="card-soft p-4 h-100">
                    <h2 class="h6 mb-3">My team ({{ $directReports->count() }})</h2>
                    @foreach($directReports as $report)
                        <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <img src="{{ $report->photo_url }}" alt="" class="rounded-circle"
                                 width="40" height="40" style="object-fit: cover;">
                            <div>
                                <div class="small fw-semibold">{{ $report->full_name }}</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $report->position?->title }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($colleagues->isNotEmpty())
            <div class="col-md-{{ $directReports->isNotEmpty() ? 6 : 12 }}">
                <div class="card-soft p-4 h-100">
                    <h2 class="h6 mb-3">{{ $employee->department?->name ?? 'My department' }}</h2>
                    @foreach($colleagues as $colleague)
                        <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <img src="{{ $colleague->photo_url }}" alt="" class="rounded-circle"
                                 width="40" height="40" style="object-fit: cover;">
                            <div>
                                <div class="small fw-semibold">{{ $colleague->full_name }}</div>
                                <div class="text-muted" style="font-size:.78rem;">{{ $colleague->position?->title }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

@endsection
