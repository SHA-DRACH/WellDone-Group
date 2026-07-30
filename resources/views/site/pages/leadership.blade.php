@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Leadership & Team',
        'subtitle' => 'The people accountable for delivery across our four divisions.',
        'breadcrumbs' => ['About' => route('site.about'), 'Leadership' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="section-title text-center">
                <span class="eyebrow">Executive</span>
                <h2>Group leadership</h2>
            </div>

            <div class="row g-4">
                @forelse($leadership as $member)
                    <div class="col-sm-6 col-lg-4">
                        <div class="card-soft h-100 p-4 text-center" data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                            <img src="{{ $member->photo_url }}" alt="{{ $member->name }}"
                                 class="rounded-circle mb-3" width="120" height="120"
                                 style="object-fit: cover;" loading="lazy">

                            <h3 class="h5 mb-1">{{ $member->name }}</h3>
                            <p class="text-gold fw-semibold small mb-1">{{ $member->position }}</p>
                            @if($member->division)
                                <p class="text-muted small mb-3">{{ $member->division->name }}</p>
                            @endif

                            @if($member->short_bio)
                                <p class="small text-muted">{{ $member->short_bio }}</p>
                            @endif

                            <div class="d-flex justify-content-center gap-2 mt-3">
                                @if($member->linkedin_url)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ $member->linkedin_url }}"
                                       target="_blank" rel="noopener noreferrer" aria-label="{{ $member->name }} on LinkedIn">
                                        <i class="fa-brands fa-linkedin-in"></i>
                                    </a>
                                @endif
                                @if($member->email)
                                    <a class="btn btn-sm btn-outline-primary" href="mailto:{{ $member->email }}"
                                       aria-label="Email {{ $member->name }}">
                                        <i class="fa-solid fa-envelope"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-solid fa-users"></i>
                            <p>Leadership profiles have not been published yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @if($team->isNotEmpty())
        <section class="section section--surface">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Management</span>
                    <h2>Functional leads</h2>
                </div>

                <div class="row g-4">
                    @foreach($team as $member)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card-soft h-100 p-3 text-center">
                                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}"
                                     class="rounded-circle mb-3" width="88" height="88"
                                     style="object-fit: cover;" loading="lazy">
                                <h3 class="h6 mb-1" style="font-size:.95rem;">{{ $member->name }}</h3>
                                <p class="text-muted mb-0" style="font-size:.8rem;">{{ $member->position }}</p>
                            </div>
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
                        <h2 class="h3">Want to join the team?</h2>
                        <p>We recruit across all four divisions, and we promote from within.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="{{ route('site.careers.index') }}" class="btn btn-gold btn-lg">View open roles</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
