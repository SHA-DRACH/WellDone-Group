@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Our Services',
        'subtitle' => 'Everything WellDone Group delivers, grouped by the division that owns it.',
        'breadcrumbs' => ['Services' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="filter-pills">
                <a href="{{ route('site.services.index') }}"
                   class="filter-pill {{ $activeDivision ? '' : 'active' }}">All divisions</a>
                @foreach($allDivisions as $division)
                    <a href="{{ route('site.services.index', ['division' => $division->slug]) }}"
                       class="filter-pill {{ $activeDivision === $division->slug ? 'active' : '' }}">{{ $division->name }}</a>
                @endforeach
            </div>

            @forelse($divisions as $division)
                <div class="{{ $loop->last ? '' : 'mb-5 pb-4' }}">
                    <div class="section-title">
                        <span class="eyebrow"><i class="{{ $division->icon }} me-1"></i>{{ $division->name }}</span>
                        <h2 class="h3">{{ $division->tagline }}</h2>
                    </div>

                    <div class="row g-4">
                        @foreach($division->activeServices as $service)
                            <div class="col-md-6 col-lg-4">
                                <x-service-card :service="$service" :delay="$loop->index * 50" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No services are published for this division yet.</p>
                </div>
            @endforelse
        </div>
    </section>

@endsection
