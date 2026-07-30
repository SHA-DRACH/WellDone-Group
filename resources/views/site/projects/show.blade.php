@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => $project->title,
        'subtitle' => $project->summary,
        'breadcrumbs' => [
            'Projects' => route('site.projects.index'),
            $project->title => null,
        ],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    {{-- Gallery: main image with thumbnail strip --}}
                    @if($project->images->isNotEmpty())
                        <div class="swiper rounded-4 overflow-hidden mb-3"
                             data-gallery-main data-gallery-thumbs="#projectThumbs">
                            <div class="swiper-wrapper">
                                @foreach($project->images as $image)
                                    <div class="swiper-slide">
                                        <img src="{{ $image->url }}" class="w-100"
                                             style="aspect-ratio: 16/10; object-fit: cover;"
                                             alt="{{ $image->alt_text ?: $project->title }}" loading="lazy">
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>

                        <div class="swiper mb-4" id="projectThumbs">
                            <div class="swiper-wrapper">
                                @foreach($project->images as $image)
                                    <div class="swiper-slide" style="cursor: pointer;">
                                        <img src="{{ $image->url }}" class="w-100 rounded-3"
                                             style="aspect-ratio: 4/3; object-fit: cover;"
                                             alt="" loading="lazy">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <img src="{{ $project->featured_image_url }}" class="w-100 rounded-4 mb-4"
                             alt="{{ $project->title }}" loading="lazy">
                    @endif

                    <div class="prose">
                        {!! $project->description !!}
                    </div>

                    @if($project->scope_of_work)
                        <h2 class="h4 mt-5 mb-3">Scope of work</h2>
                        <div class="prose">{!! $project->scope_of_work !!}</div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card-soft p-4 mb-4">
                        <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Project details</h2>

                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted fw-normal">Status</dt>
                            <dd class="col-7">
                                <span class="status-badge status-badge--{{ $project->status }}">{{ $project->status_label }}</span>
                            </dd>

                            @if($project->division)
                                <dt class="col-5 text-muted fw-normal">Division</dt>
                                <dd class="col-7">
                                    <a href="{{ route('site.divisions.show', $project->division->slug) }}">{{ $project->division->name }}</a>
                                </dd>
                            @endif

                            @if($project->public_client_name)
                                <dt class="col-5 text-muted fw-normal">Client</dt>
                                <dd class="col-7">{{ $project->public_client_name }}</dd>
                            @endif

                            @if($project->location)
                                <dt class="col-5 text-muted fw-normal">Location</dt>
                                <dd class="col-7">{{ $project->location }}</dd>
                            @endif

                            @if($project->duration_label)
                                <dt class="col-5 text-muted fw-normal">Duration</dt>
                                <dd class="col-7">{{ $project->duration_label }}</dd>
                            @endif

                            @if($project->show_value && $project->contract_value)
                                <dt class="col-5 text-muted fw-normal">Contract value</dt>
                                <dd class="col-7">@money($project->contract_value, $project->currency)</dd>
                            @endif
                        </dl>

                        @if($project->status === 'ongoing')
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Progress</span>
                                    <strong>{{ $project->completion_percentage }}%</strong>
                                </div>
                                <div class="progress" style="height: 8px;" role="progressbar"
                                     aria-valuenow="{{ $project->completion_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-warning" style="width: {{ $project->completion_percentage }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($project->key_facts)
                        <div class="card-soft p-4 mb-4">
                            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Key facts</h2>
                            <dl class="row small mb-0">
                                @foreach($project->key_facts as $label => $value)
                                    <dt class="col-7 text-muted fw-normal">{{ $label }}</dt>
                                    <dd class="col-5 text-end fw-semibold">{{ $value }}</dd>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    <div class="card-soft p-4">
                        <h2 class="h6">Have a similar requirement?</h2>
                        <p class="small text-muted">We would be glad to discuss scope, timing and cost.</p>
                        <a href="{{ route('site.quote.create') }}" class="btn btn-primary w-100">Request a Quote</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($related->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">More work</span>
                    <h2>Related projects</h2>
                </div>
                <div class="row g-4">
                    @foreach($related as $item)
                        <div class="col-md-6 col-lg-4">
                            <x-project-card :project="$item" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
