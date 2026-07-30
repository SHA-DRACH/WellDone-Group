@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Careers',
        'subtitle' => 'Build a career with a Liberian group that invests in its people.',
        'breadcrumbs' => ['Careers' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <form method="GET" action="{{ route('site.careers.index') }}" class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="career-division">Division</label>
                            <select class="form-select" id="career-division" name="division" data-auto-submit>
                                <option value="">All divisions</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->slug }}" @selected(($filters['division'] ?? null) === $division->slug)>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="career-type">Employment type</label>
                            <select class="form-select" id="career-type" name="type" data-auto-submit>
                                <option value="">Any type</option>
                                @foreach(\App\Models\Career::EMPLOYMENT_TYPES as $type)
                                    <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>
                                        {{ ucwords(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    @forelse($vacancies as $vacancy)
                        <article class="card-soft p-4 mb-3" data-aos="fade-up">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    @if($vacancy->is_featured)
                                        <span class="category-chip mb-2 d-inline-block" style="background: rgba(212,175,55,.18); color:#8a6d16;">
                                            Featured
                                        </span>
                                    @endif

                                    <h2 class="h5 mb-2">
                                        <a href="{{ $vacancy->url }}" class="text-decoration-none text-reset stretched-link">
                                            {{ $vacancy->title }}
                                        </a>
                                    </h2>

                                    <ul class="list-inline small text-muted mb-0">
                                        @if($vacancy->division)
                                            <li class="list-inline-item me-3">
                                                <i class="fa-solid fa-sitemap me-1"></i>{{ $vacancy->division->name }}
                                            </li>
                                        @endif
                                        @if($vacancy->location)
                                            <li class="list-inline-item me-3">
                                                <i class="fa-solid fa-location-dot me-1"></i>{{ $vacancy->location }}
                                            </li>
                                        @endif
                                        <li class="list-inline-item me-3">
                                            <i class="fa-regular fa-clock me-1"></i>{{ $vacancy->employment_type_label }}
                                        </li>
                                        <li class="list-inline-item">
                                            <i class="fa-solid fa-layer-group me-1"></i>{{ $vacancy->experience_level_label }} level
                                        </li>
                                    </ul>
                                </div>

                                <div class="text-lg-end">
                                    @if($vacancy->closes_at)
                                        <div class="small text-muted mb-2">
                                            Closes {{ $vacancy->closes_at->format('j M Y') }}
                                        </div>
                                    @endif
                                    <span class="btn btn-outline-primary btn-sm">View &amp; apply</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <i class="fa-solid fa-briefcase"></i>
                            <p>There are no open vacancies matching your filters at the moment.</p>
                            <p class="small">
                                We still welcome speculative applications —
                                <a href="{{ route('site.contact') }}">get in touch</a>.
                            </p>
                        </div>
                    @endforelse

                    <div class="mt-4">{{ $vacancies->links() }}</div>
                </div>

                <aside class="col-lg-4">
                    <div class="card-soft p-4 mb-4">
                        <h2 class="h6">Why work with us</h2>
                        <ul class="list-unstyled small mb-0">
                            <li class="d-flex gap-2 mb-3">
                                <i class="fa-solid fa-graduation-cap text-gold mt-1"></i>
                                <span>Structured training and certification support across every division.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="fa-solid fa-helmet-safety text-gold mt-1"></i>
                                <span>A safety culture where anyone can stop a job without consequence.</span>
                            </li>
                            <li class="d-flex gap-2 mb-3">
                                <i class="fa-solid fa-arrow-trend-up text-gold mt-1"></i>
                                <span>Internal promotion — several of our supervisors began as interns.</span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="fa-solid fa-people-group text-gold mt-1"></i>
                                <span>Priority recruitment from the communities that host our operations.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="card-soft p-4">
                        <h2 class="h6">Questions about recruitment?</h2>
                        <p class="small text-muted">
                            Our HR team is happy to help with anything about a role or the application process.
                        </p>
                        @if($hr = setting('contact.email_hr'))
                            <a href="mailto:{{ $hr }}" class="btn btn-outline-primary btn-sm w-100">{{ $hr }}</a>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </section>

@endsection
