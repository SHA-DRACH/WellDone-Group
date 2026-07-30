@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'About WellDone Group',
        'subtitle' => setting('company.tagline'),
        'breadcrumbs' => ['About' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-7">
                    <div class="section-title">
                        <span class="eyebrow">Our Story</span>
                        <h2>{{ setting('company.name') }}</h2>
                    </div>
                    <div class="prose">{!! setting('company.history') !!}</div>
                </div>

                <div class="col-lg-5">
                    <div class="card-soft p-4 mb-4">
                        <span class="icon-badge mb-3"><i class="fa-solid fa-bullseye"></i></span>
                        <h3 class="h5">Our Mission</h3>
                        <p class="text-muted mb-0">{{ setting('company.mission') }}</p>
                    </div>

                    <div class="card-soft p-4 mb-4">
                        <span class="icon-badge icon-badge--gold mb-3"><i class="fa-solid fa-eye"></i></span>
                        <h3 class="h5">Our Vision</h3>
                        <p class="text-muted mb-0">{{ setting('company.vision') }}</p>
                    </div>

                    @if($profile = setting('company.profile_pdf'))
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile) }}"
                           class="btn btn-primary w-100" download>
                            <i class="fa-solid fa-file-arrow-down me-2"></i>Download Company Profile
                        </a>
                    @else
                        <a href="{{ route('site.downloads.index') }}" class="btn btn-outline-primary w-100">
                            <i class="fa-solid fa-folder-open me-2"></i>Company documents
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Core values --}}
    @if($values !== [])
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">What We Stand For</span>
                    <h2>Our core values</h2>
                </div>

                <div class="row g-4">
                    @foreach($values as $value)
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                            <div class="card-soft h-100 p-4">
                                <span class="icon-badge mb-3"><i class="{{ $value['icon'] ?? 'fa-solid fa-star' }}"></i></span>
                                <h3 class="h6">{{ $value['title'] ?? '' }}</h3>
                                <p class="small text-muted mb-0">{{ $value['description'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Organisational structure --}}
    <section class="section">
        <div class="container">
            <div class="section-title text-center">
                <span class="eyebrow">Organisational Structure</span>
                <h2>How the group is organised</h2>
                <p>A lean group function sets standards and governance; each division holds its own licences, specialists and delivery accountability.</p>
            </div>

            <div class="row justify-content-center mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card-soft p-4 text-center border-primary">
                        <span class="icon-badge mx-auto mb-3"><i class="fa-solid fa-building-columns"></i></span>
                        <h3 class="h6 mb-1">Group Executive</h3>
                        <p class="small text-muted mb-0">Strategy, governance, finance, HSE and shared services</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach($divisions as $division)
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('site.divisions.show', $division->slug) }}"
                           class="card-soft p-4 h-100 text-center d-block text-decoration-none text-reset">
                            <span class="icon-badge icon-badge--gold mx-auto mb-3">
                                <i class="{{ $division->icon }}"></i>
                            </span>
                            <h3 class="h6 mb-1">{{ $division->name }}</h3>
                            <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit($division->tagline, 68) }}</p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Milestones --}}
    @if($milestones->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Company History</span>
                    <h2>Milestones</h2>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        @foreach($milestones as $milestone)
                            <div class="d-flex gap-4 {{ $loop->last ? '' : 'pb-4 mb-4 border-bottom' }}"
                                 data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                                <div class="text-center flex-shrink-0" style="width: 96px;">
                                    <span class="icon-badge icon-badge--gold mb-2"><i class="{{ $milestone->icon }}"></i></span>
                                    <div class="fw-bold text-primary">{{ $milestone->year }}</div>
                                </div>
                                <div>
                                    <h3 class="h6 mb-1">{{ $milestone->title }}</h3>
                                    <p class="text-muted small mb-0">{{ $milestone->description }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Leadership --}}
    @if($leadership->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="section-title mb-0">
                        <span class="eyebrow">Leadership</span>
                        <h2>Directing the group</h2>
                    </div>
                    <a href="{{ route('site.leadership') }}" class="btn btn-outline-primary">
                        Full team <i class="fa-solid fa-arrow-right-long ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($leadership as $member)
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="card-soft p-3 text-center h-100">
                                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}"
                                     class="rounded-circle mb-3" width="88" height="88"
                                     style="object-fit: cover;" loading="lazy">
                                <h3 class="h6 mb-1" style="font-size:.92rem;">{{ $member->name }}</h3>
                                <p class="text-muted mb-0" style="font-size:.78rem;">{{ $member->position }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- CSR teaser --}}
    <section class="section section--ink">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="eyebrow d-inline-block mb-2" style="color:#e5c766; letter-spacing:.14em; font-size:.8rem; font-weight:700;">
                        CORPORATE SOCIAL RESPONSIBILITY
                    </span>
                    <h2>Investing in the communities that host us</h2>
                    <p class="mb-0">{{ setting('company.csr_statement') }}</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('site.csr') }}" class="btn btn-gold btn-lg">Our CSR work</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Certifications --}}
    @if($certificates->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="section-title mb-0">
                        <span class="eyebrow">Compliance</span>
                        <h2>Registrations &amp; certifications</h2>
                    </div>
                    <a href="{{ route('site.certifications') }}" class="btn btn-outline-primary">
                        View all <i class="fa-solid fa-arrow-right-long ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($certificates as $certificate)
                        <div class="col-md-6 col-lg-4">
                            <div class="card-soft p-4 h-100 d-flex gap-3">
                                <span class="icon-badge flex-shrink-0"><i class="fa-solid fa-certificate"></i></span>
                                <div>
                                    <h3 class="h6 mb-1">{{ $certificate->title }}</h3>
                                    <p class="small text-muted mb-0">{{ $certificate->issuer }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
