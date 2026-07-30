@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => $service->name,
        'subtitle' => $service->summary,
        'breadcrumbs' => [
            'Divisions' => route('site.divisions.index'),
            ($service->division?->name ?? 'Services') => $service->division ? route('site.divisions.show', $service->division->slug) : route('site.services.index'),
            $service->name => null,
        ],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    @if($service->image_url)
                        <img src="{{ $service->image_url }}" class="w-100 rounded-4 mb-4"
                             alt="{{ $service->name }}" loading="lazy">
                    @endif

                    <div class="prose">
                        {!! $service->description !!}
                    </div>

                    @if($service->features)
                        <h2 class="h4 mt-5 mb-3">What's included</h2>
                        <div class="row g-3">
                            @foreach($service->features as $feature)
                                <div class="col-sm-6">
                                    <div class="d-flex gap-2 align-items-start">
                                        <i class="fa-solid fa-circle-check text-gold mt-1"></i>
                                        <span>{{ $feature }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card-soft p-4 mb-4">
                        <span class="icon-badge mb-3"><i class="{{ $service->icon }}" aria-hidden="true"></i></span>
                        <h2 class="h5">Request this service</h2>
                        <p class="small text-muted">
                            Tell us what you need and we will respond with a written quotation.
                        </p>
                        <a href="{{ route('site.quote.create', ['service' => $service->id]) }}" class="btn btn-primary w-100 mb-2">
                            Request a Quote
                        </a>
                        <a href="{{ route('site.contact') }}" class="btn btn-outline-primary w-100">Ask a question</a>
                    </div>

                    @if($related->isNotEmpty())
                        <div class="card-soft p-4">
                            <h3 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                                Related services
                            </h3>
                            <ul class="list-unstyled mb-0">
                                @foreach($related as $item)
                                    <li class="mb-2 pb-2 border-bottom">
                                        <a href="{{ $item->url }}" class="d-flex gap-2 align-items-start text-decoration-none">
                                            <i class="{{ $item->icon }} text-primary mt-1"></i>
                                            <span class="small fw-semibold">{{ $item->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

@endsection
