{{-- $navDivisions is supplied by App\Providers\ViewServiceProvider --}}
<div class="topbar d-none d-lg-block">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <ul class="topbar-list">
                @if($phone = setting('contact.phone_primary'))
                    <li>
                        <i class="fa-solid fa-phone me-2 text-gold"></i>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>
                    </li>
                @endif
                @if($email = setting('contact.email_general'))
                    <li>
                        <i class="fa-solid fa-envelope me-2 text-gold"></i>
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </li>
                @endif
                <li>
                    <i class="fa-solid fa-location-dot me-2 text-gold"></i>
                    {{ setting('contact.city') }}, {{ setting('contact.country') }}
                </li>
            </ul>

            <ul class="topbar-list">
                <li><a href="{{ route('site.careers.index') }}">Careers</a></li>
                <li><a href="{{ route('site.downloads.index') }}">Downloads</a></li>
                <li><a href="{{ route('site.faqs') }}">FAQs</a></li>

                {{-- Signed-in users are linked to whichever home their role
                     implies: clients to their account, staff to a dashboard. --}}
                @auth
                    <li>
                        <a href="{{ \App\Support\Navigation::homeRouteFor(auth()->user()) }}" class="fw-semibold">
                            <i class="fa-solid fa-{{ auth()->user()->isClient() ? 'circle-user' : 'gauge-high' }} me-1"></i>
                            {{ auth()->user()->isClient() ? 'My Account' : 'My Dashboard' }}
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link p-0 align-baseline text-decoration-none"
                                    style="color: rgba(255,255,255,.82); font-size: inherit;">
                                Sign out
                            </button>
                        </form>
                    </li>
                @else
                    <li>
                        <a href="{{ route('login') }}" class="fw-semibold">
                            <i class="fa-solid fa-right-to-bracket me-1"></i>Sign In
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('register') }}" class="fw-semibold">
                            <i class="fa-solid fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                @endauth
                @foreach(['facebook' => 'facebook-f', 'linkedin' => 'linkedin-in', 'twitter' => 'x-twitter', 'instagram' => 'instagram', 'youtube' => 'youtube'] as $key => $icon)
                    @if($url = setting("social.$key"))
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($key) }}">
                                <i class="fa-brands fa-{{ $icon }}"></i>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>

<header class="site-header">
    <nav class="navbar navbar-expand-lg py-0" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand" href="{{ route('site.home') }}">
                @if(\App\Support\Brand::hasLogo())
                    <img src="{{ brand('logo') }}" class="brand-logo"
                         alt="{{ setting('company.name') }}" width="96" height="62">
                    <span class="brand-text">
                        <strong>{{ setting('company.name') }}</strong>
                        <span>{{ setting('company.slogan') }}</span>
                    </span>
                @else
                    <span class="brand-mark" aria-hidden="true"><strong>WD</strong></span>
                    <span class="brand-text">
                        <strong>WellDone Group</strong>
                        <span>Incorporated</span>
                    </span>
                @endif
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#primaryNav" aria-controls="primaryNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="primaryNav">
                <ul class="navbar-nav main-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ active_when('site.home') }}" href="{{ route('site.home') }}">Home</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ active_when(['site.about', 'site.leadership', 'site.csr', 'site.certifications']) }}"
                           href="{{ route('site.about') }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            About
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('site.about') }}">Company Overview</a></li>
                            <li><a class="dropdown-item" href="{{ route('site.leadership') }}">Leadership &amp; Team</a></li>
                            <li><a class="dropdown-item" href="{{ route('site.csr') }}">Corporate Responsibility</a></li>
                            <li><a class="dropdown-item" href="{{ route('site.certifications') }}">Certifications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('site.gallery.index') }}">Gallery</a></li>
                        </ul>
                    </li>

                    {{-- Mega menu: one column per division, populated from the database --}}
                    <li class="nav-item dropdown has-megamenu">
                        <a class="nav-link dropdown-toggle {{ active_when(['site.divisions.*', 'site.services.*']) }}"
                           href="{{ route('site.divisions.index') }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Divisions
                        </a>

                        <div class="dropdown-menu megamenu">
                            <div class="megamenu-inner">
                                <div class="row g-4">
                                    <div class="col-lg-9">
                                        <div class="row g-4">
                                            @foreach($navDivisions as $division)
                                                <div class="col-md-6 col-xl-3">
                                                    <p class="megamenu-heading">
                                                        <i class="{{ $division->icon }} me-1"></i>
                                                        {{ $division->name }}
                                                    </p>
                                                    <ul class="list-unstyled mb-2">
                                                        @foreach($division->activeServices as $service)
                                                            <li>
                                                                <a class="megamenu-link" href="{{ route('site.services.show', $service->slug) }}">
                                                                    <i class="{{ $service->icon }}" aria-hidden="true"></i>
                                                                    <span><strong>{{ $service->name }}</strong></span>
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <a class="small fw-semibold" href="{{ route('site.divisions.show', $division->slug) }}">
                                                        View division <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="megamenu-promo d-flex flex-column justify-content-between">
                                            <div>
                                                <h4>Need a quotation?</h4>
                                                <p>Select the services you need and tell us about your project. We reply with a written quotation.</p>
                                            </div>
                                            <a href="{{ route('site.quote.create') }}" class="btn btn-gold btn-sm w-100">
                                                Request a Quote
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ active_when('site.projects.*') }}" href="{{ route('site.projects.index') }}">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ active_when('site.blog.*') }}" href="{{ route('site.blog.index') }}">News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ active_when('site.contact*') }}" href="{{ route('site.contact') }}">Contact</a>
                    </li>

                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <a class="btn btn-primary w-100" href="{{ route('site.quote.create') }}">
                            <i class="fa-solid fa-file-invoice me-2"></i>Request a Quote
                        </a>
                    </li>

                    {{-- The top bar carries the staff link on desktop, but it
                         is hidden below lg — so repeat it inside the collapse
                         for mobile, and keep a compact icon on desktop. --}}
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        @auth
                            @php($home = \App\Support\Navigation::homeRouteFor(auth()->user()))
                            @php($icon = auth()->user()->isClient() ? 'circle-user' : 'gauge-high')
                            @php($label = auth()->user()->isClient() ? 'My Account' : 'My Dashboard')

                            <a class="btn btn-outline-primary w-100 d-lg-none" href="{{ $home }}">
                                <i class="fa-solid fa-{{ $icon }} me-2"></i>{{ $label }}
                            </a>
                            <a class="btn btn-outline-primary d-none d-lg-inline-flex" href="{{ $home }}"
                               data-bs-toggle="tooltip" title="{{ $label }}" aria-label="{{ $label }}">
                                <i class="fa-solid fa-{{ $icon }}"></i>
                            </a>
                        @else
                            <a class="btn btn-outline-primary w-100 d-lg-none mb-2" href="{{ route('login') }}">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                            </a>
                            <a class="btn btn-primary w-100 d-lg-none" href="{{ route('register') }}">
                                <i class="fa-solid fa-user-plus me-2"></i>Create an account
                            </a>
                            <a class="btn btn-outline-primary d-none d-lg-inline-flex" href="{{ route('login') }}"
                               data-bs-toggle="tooltip" title="Sign in or create an account" aria-label="Sign in">
                                <i class="fa-solid fa-right-to-bracket"></i>
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
