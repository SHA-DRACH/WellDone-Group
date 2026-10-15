@extends('site.layouts.app')

@section('content')

    {{-- ============================================ Hero slider --}}
    @if($slides->isNotEmpty())
        <section class="hero" aria-label="Featured">
            <div class="swiper" data-hero-slider>
                <div class="swiper-wrapper">
                    @foreach($slides as $slide)
                        <div class="swiper-slide">
                            <div class="hero-slide" style="background-image: url('{{ $slide->image_url }}');">
                                <div class="container">
                                    <div class="hero-content">
                                        @if($slide->eyebrow)
                                            <span class="hero-eyebrow">{{ $slide->eyebrow }}</span>
                                        @endif

                                        <h1>
                                            {{ $slide->title }}
                                            @if($slide->highlight)
                                                <span class="accent d-block">{{ $slide->highlight }}</span>
                                            @endif
                                        </h1>

                                        @if($slide->slogan)
                                            <p class="hero-slogan h5">{{ $slide->slogan }}</p>
                                        @endif

                                        @if($slide->description)
                                            <p class="hero-lead">{{ $slide->description }}</p>
                                        @endif

                                        <div class="d-flex flex-wrap gap-3">
                                            @if($slide->primary_button_text)
                                                <a href="{{ $slide->primary_button_url ?: route('site.quote.create') }}" class="btn btn-gold btn-lg">
                                                    {{ $slide->primary_button_text }}
                                                    <i class="fa-solid fa-arrow-right-long ms-2"></i>
                                                </a>
                                            @endif
                                            @if($slide->secondary_button_text)
                                                <a href="{{ $slide->secondary_button_url ?: route('site.services.index') }}" class="btn btn-outline-light btn-lg">
                                                    {{ $slide->secondary_button_text }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </section>
    @endif

    {{-- ============================================ Statistics --}}
    @php($stats = setting('home.stats', []))
    @if(is_array($stats) && $stats !== [])
        <section class="stat-strip">
            <div class="container">
                <div class="row">
                    @foreach($stats as $stat)
                        <div class="col-6 col-lg-3">
                            <div class="stat">
                                @if(!empty($stat['icon']))
                                    <div class="stat-icon"><i class="{{ $stat['icon'] }}" aria-hidden="true"></i></div>
                                @endif
                                <div class="stat-value"
                                     data-counter="{{ $stat['value'] ?? 0 }}"
                                     data-suffix="{{ $stat['suffix'] ?? '' }}"
                                     data-prefix="{{ $stat['prefix'] ?? '' }}">{{ $stat['value'] ?? 0 }}</div>
                                <div class="stat-label">{{ $stat['label'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Welcome video --}}
    @if($welcomeVideo)
        <section class="section welcome-video" aria-labelledby="welcome-video-heading">
            <div class="container position-relative" style="z-index: 1;">
                <div class="row align-items-center g-5">
                    <div class="col-lg-5" data-aos="fade-right">
                        <span class="eyebrow">Welcome</span>
                        <h2 id="welcome-video-heading" class="mb-3">{{ $welcomeVideo['heading'] }}</h2>
                        @if($welcomeVideo['body'])
                            <p class="lead mb-4">{{ $welcomeVideo['body'] }}</p>
                        @endif

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <a href="{{ route('site.about') }}" class="btn btn-gold">
                                Discover our story <i class="fa-solid fa-arrow-right-long ms-2"></i>
                            </a>
                            @if($welcomeVideo['duration'])
                                <span class="video-length">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    {{ \App\Rules\VideoLength::human($welcomeVideo['duration']) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-7" data-aos="fade-left">
                        <x-video-player :src="$welcomeVideo['src']" :embed="$welcomeVideo['embed']"
                                        :poster="$welcomeVideo['poster']" :title="$welcomeVideo['heading']" />
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ About --}}
    <section class="section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="section-title">
                        <span class="eyebrow">About WellDone Group</span>
                        <h2>{{ setting('home.about_heading') }}</h2>
                        <p>{{ setting('home.about_body') }}</p>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-sm-6">
                            <div class="feature-item">
                                <span class="feature-icon"><i class="fa-solid fa-bullseye"></i></span>
                                <div>
                                    <h4>Our Mission</h4>
                                    <p>{{ \Illuminate\Support\Str::limit(setting('company.mission'), 130) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="feature-item">
                                <span class="feature-icon"><i class="fa-solid fa-eye"></i></span>
                                <div>
                                    <h4>Our Vision</h4>
                                    <p>{{ \Illuminate\Support\Str::limit(setting('company.vision'), 130) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('site.about') }}" class="btn btn-primary">More About Us</a>
                        <a href="{{ route('site.leadership') }}" class="btn btn-outline-primary">Meet the Team</a>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="row g-3">
                        @foreach($divisions->take(4) as $division)
                            <div class="col-sm-6">
                                <a href="{{ route('site.divisions.show', $division->slug) }}"
                                   class="card-soft d-block h-100 p-4 text-decoration-none text-reset">
                                    <span class="icon-badge icon-badge--gold mb-3">
                                        <i class="{{ $division->icon }}" aria-hidden="true"></i>
                                    </span>
                                    <h3 class="h6 mb-1">{{ $division->name }}</h3>
                                    <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit($division->tagline, 70) }}</p>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================ Divisions --}}
    <section class="section section--surface">
        <div class="container">
            <div class="section-title text-center">
                <span class="eyebrow">Business Divisions</span>
                <h2>Four divisions, one standard of delivery</h2>
                <p>Each division operates with its own specialists and licences, and reports to the same group standards for safety, compliance and client communication.</p>
            </div>

            <div class="row g-4">
                @foreach($divisions as $division)
                    <div class="col-md-6 col-xl-3" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <article class="division-card h-100">
                            <div class="division-card__media">
                                <img src="{{ $division->thumbnail_url }}" alt="" loading="lazy" decoding="async">
                            </div>
                            <div class="division-card__body">
                                <span class="division-card__icon"><i class="{{ $division->icon }}" aria-hidden="true"></i></span>
                                <h3>{{ $division->name }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($division->tagline ?: $division->summary, 95) }}</p>
                                <a href="{{ route('site.divisions.show', $division->slug) }}" class="stretched-link text-decoration-none">
                                    Explore division <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================ Why choose us --}}
    @php($reasons = setting('home.why_choose_us', []))
    @if(is_array($reasons) && $reasons !== [])
        <section class="section">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Why Choose Us</span>
                    <h2>What you get when you contract WellDone</h2>
                </div>

                <div class="row g-4">
                    @foreach($reasons as $reason)
                        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                            <div class="card-soft h-100 p-4">
                                <span class="icon-badge mb-3"><i class="{{ $reason['icon'] ?? 'fa-solid fa-check' }}" aria-hidden="true"></i></span>
                                <h3 class="h6">{{ $reason['title'] ?? '' }}</h3>
                                <p class="small text-muted mb-0">{{ $reason['description'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Featured projects --}}
    @if($projects->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="section-title mb-0">
                        <span class="eyebrow">Selected Work</span>
                        <h2>Projects delivered</h2>
                    </div>
                    <a href="{{ route('site.projects.index') }}" class="btn btn-outline-primary">
                        All projects <i class="fa-solid fa-arrow-right-long ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($projects as $project)
                        <div class="col-md-6 col-lg-4">
                            <x-project-card :project="$project" :delay="$loop->index * 70" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Testimonials --}}
    @if($testimonials->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Client Feedback</span>
                    <h2>What our clients say</h2>
                </div>

                <div class="swiper pb-5" data-slider data-per-view="3" data-gap="24">
                    <div class="swiper-wrapper">
                        @foreach($testimonials as $testimonial)
                            <div class="swiper-slide h-auto">
                                <x-testimonial-card :testimonial="$testimonial" />
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Partners & accreditations --}}
    @if($partners->isNotEmpty())
        <section class="section section--tight section--surface">
            <div class="container">
                <div class="section-title text-center mb-4">
                    <span class="eyebrow">Partners &amp; Accreditations</span>
                    <h2 class="h4 mb-0">Registered, licensed and affiliated</h2>
                </div>

                <div class="row g-3 justify-content-center">
                    @foreach($partners as $partner)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <div class="partner-logo" title="{{ $partner->name }}">
                                @if($partner->logo)
                                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy">
                                @else
                                    <span class="small text-center text-muted fw-semibold">{{ $partner->name }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Latest news --}}
    @if($posts->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="section-title mb-0">
                        <span class="eyebrow">Newsroom</span>
                        <h2>Latest news &amp; insight</h2>
                    </div>
                    <a href="{{ route('site.blog.index') }}" class="btn btn-outline-primary">
                        All articles <i class="fa-solid fa-arrow-right-long ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($posts as $post)
                        <div class="col-md-6 col-lg-4">
                            <x-post-card :post="$post" :delay="$loop->index * 70" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================ Call to action --}}
    <section class="section section--tight">
        <div class="container">
            <div class="cta-panel" data-aos="zoom-in">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2>{{ setting('home.cta_heading') }}</h2>
                        <p>{{ setting('home.cta_body') }}</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="{{ route('site.quote.create') }}" class="btn btn-gold btn-lg w-100 w-lg-auto mb-2">
                            Request a Quote
                        </a>
                        <a href="{{ route('site.contact') }}" class="btn btn-outline-light w-100 w-lg-auto">
                            Talk to our team
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
