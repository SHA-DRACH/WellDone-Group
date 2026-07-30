@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Business Divisions',
        'subtitle' => 'Four specialist divisions operating under one group standard for safety, compliance and delivery.',
        'breadcrumbs' => ['Divisions' => null],
    ])

    <section class="section">
        <div class="container">
            @foreach($divisions as $division)
                <div class="row align-items-center g-4 g-lg-5 {{ $loop->last ? '' : 'mb-5 pb-4 border-bottom' }}">
                    <div class="col-lg-5 {{ $loop->odd ? '' : 'order-lg-2' }}" data-aos="fade-up">
                        <div class="division-card" style="min-height: 340px;">
                            <div class="division-card__media">
                                <img src="{{ $division->thumbnail_url }}" alt="" loading="lazy" decoding="async">
                            </div>
                            <div class="division-card__body">
                                <span class="division-card__icon"><i class="{{ $division->icon }}" aria-hidden="true"></i></span>
                                <h3 class="mb-0">{{ $division->name }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 {{ $loop->odd ? '' : 'order-lg-1' }}" data-aos="fade-up" data-aos-delay="80">
                        <div class="section-title mb-3">
                            <span class="eyebrow">{{ $division->services_count }} services · {{ $division->projects_count }} projects</span>
                            <h2 class="h3">{{ $division->name }}</h2>
                            <p>{{ $division->summary }}</p>
                        </div>

                        @if($division->highlights)
                            <ul class="list-unstyled row g-2 mb-4">
                                @foreach($division->highlights as $highlight)
                                    <li class="col-sm-6 d-flex gap-2">
                                        <i class="fa-solid fa-circle-check text-gold mt-1"></i>
                                        <span class="small">{{ $highlight }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('site.divisions.show', $division->slug) }}" class="btn btn-primary">
                                Explore {{ $division->name }}
                            </a>
                            <a href="{{ route('site.quote.create', ['division' => $division->id]) }}" class="btn btn-outline-primary">
                                Request a quote
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

@endsection
