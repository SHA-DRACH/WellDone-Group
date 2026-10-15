@extends('site.layouts.app')

@section('content')

    <section class="page-header" style="background-image: linear-gradient(115deg, rgba(8,43,105,.94), rgba(11,60,145,.82)), url('{{ $division->hero_url }}'); background-size: cover; background-position: center;">
        @if(\App\Support\Brand::hasLogo() && setting('company.watermark_header', true))
            <img src="{{ brand('logo_light') }}" alt="" aria-hidden="true"
                 class="page-header__watermark" loading="lazy" decoding="async">
        @endif

        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('site.home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('site.divisions.index') }}">Divisions</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $division->name }}</li>
                </ol>
            </nav>

            <span class="hero-eyebrow"><i class="{{ $division->icon }} me-2"></i>Business Division</span>
            <h1>{{ $division->name }}</h1>
            <p>{{ $division->tagline }}</p>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('site.quote.create', ['division' => $division->id]) }}" class="btn btn-gold">Request a Quote</a>
                @if($division->brochure_url)
                    <a href="{{ $division->brochure_url }}" class="btn btn-outline-light" download>
                        <i class="fa-solid fa-file-arrow-down me-2"></i>Capability Statement
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Overview --}}
    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="prose">
                        {!! $division->description !!}
                        {!! $division->capabilities !!}
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-soft p-4 position-sticky" style="top: 100px;">
                        <h3 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">At a glance</h3>

                        @if($division->highlights)
                            <ul class="list-unstyled mb-4">
                                @foreach($division->highlights as $highlight)
                                    <li class="d-flex gap-2 mb-3">
                                        <i class="fa-solid fa-circle-check text-gold mt-1"></i>
                                        <span class="small">{{ $highlight }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <a href="{{ route('site.contact') }}" class="btn btn-primary w-100 mb-2">Talk to this division</a>
                        <a href="{{ route('site.projects.index', ['division' => $division->slug]) }}" class="btn btn-outline-primary w-100">
                            See our projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Division video --}}
    @if($division->hasVideo())
        <section class="section welcome-video" aria-labelledby="division-video-heading">
            <div class="container position-relative" style="z-index: 1;">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7 order-lg-2" data-aos="fade-left">
                        <x-video-player :src="$division->video_file_url"
                                        :embed="$division->video_path ? null : $division->video_embed"
                                        :poster="$division->video_poster_url"
                                        :title="$division->name.' — introduction'" />
                    </div>
                    <div class="col-lg-5 order-lg-1" data-aos="fade-right">
                        <span class="eyebrow"><i class="{{ $division->icon }} me-2"></i>{{ $division->name }}</span>
                        <h2 id="division-video-heading" class="mb-3">See the division at work</h2>
                        <p class="mb-4">{{ $division->summary ?: $division->tagline }}</p>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <a href="{{ route('site.quote.create', ['division' => $division->id]) }}" class="btn btn-gold">
                                Request a quote <i class="fa-solid fa-arrow-right-long ms-2"></i>
                            </a>
                            @if($division->video_path && $division->video_duration)
                                <span class="video-length">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    {{ \App\Rules\VideoLength::human($division->video_duration) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Services --}}
    @if($division->activeServices->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Services</span>
                    <h2>What this division delivers</h2>
                </div>

                <div class="row g-4">
                    @foreach($division->activeServices as $service)
                        <div class="col-md-6 col-lg-4">
                            <x-service-card :service="$service" :delay="$loop->index * 60" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Projects --}}
    @if($projects->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="section-title mb-0">
                        <span class="eyebrow">Track Record</span>
                        <h2>Projects in this division</h2>
                    </div>
                    <a href="{{ route('site.projects.index', ['division' => $division->slug]) }}" class="btn btn-outline-primary">
                        View all <i class="fa-solid fa-arrow-right-long ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($projects as $project)
                        <div class="col-md-6 col-lg-4">
                            <x-project-card :project="$project" :delay="$loop->index * 60" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Gallery --}}
    @php($galleryImages = $division->galleries->flatMap->images->take(8))
    @if($galleryImages->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Gallery</span>
                    <h2>{{ $division->name }} in the field</h2>
                </div>

                <div class="row g-3">
                    @foreach($galleryImages as $image)
                        <div class="col-6 col-md-4 col-lg-3">
                            <img src="{{ $image->url }}" class="w-100 rounded-3"
                                 style="aspect-ratio: 4/3; object-fit: cover;"
                                 alt="{{ $image->alt_text }}" loading="lazy" decoding="async">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQs --}}
    @if($division->faqs->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="section-title text-center">
                            <span class="eyebrow">Questions</span>
                            <h2>Frequently asked</h2>
                        </div>

                        <div class="accordion" id="divisionFaqs">
                            @foreach($division->faqs as $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="faq{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         data-bs-parent="#divisionFaqs">
                                        <div class="accordion-body prose">{!! $faq->answer !!}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if($testimonials->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Client Feedback</span>
                    <h2>What clients say about this division</h2>
                </div>
                <div class="row g-4">
                    @foreach($testimonials as $testimonial)
                        <div class="col-lg-4">
                            <x-testimonial-card :testimonial="$testimonial" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section section--tight">
        <div class="container">
            <div class="cta-panel">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="h3">Ready to discuss a {{ strtolower($division->name) }} requirement?</h2>
                        <p>Tell us the scope and we will return a written quotation.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="{{ route('site.quote.create', ['division' => $division->id]) }}" class="btn btn-gold btn-lg">
                            Request a Quote
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
