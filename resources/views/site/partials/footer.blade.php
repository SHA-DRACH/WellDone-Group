@if(setting('system.enable_newsletter', true))
    <section class="section section--tight" style="background: var(--wd-surface);">
        <div class="container">
            <div class="cta-panel">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <h2 class="h3 mb-2">Stay informed</h2>
                        <p>Project updates, tenders and company news — a few times a month, no more.</p>
                    </div>
                    <div class="col-lg-6">
                        <form action="{{ route('site.newsletter.subscribe') }}" method="POST" class="newsletter-form">
                            @csrf
                            {{-- Honeypot: hidden from people, tempting to bots --}}
                            <div class="d-none" aria-hidden="true">
                                <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <div class="input-group input-group-lg">
                                <label class="visually-hidden" for="newsletter-email">Email address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="newsletter-email" name="email" placeholder="your.name@company.com"
                                       value="{{ old('email') }}" required>
                                <button class="btn btn-gold" type="submit">Subscribe</button>
                            </div>

                            @error('email')
                                <div class="text-warning small mt-2">{{ $message }}</div>
                            @enderror

                            @if(session('newsletter_status'))
                                <div class="small mt-2 text-white">
                                    <i class="fa-solid fa-circle-check me-1 text-gold"></i>{{ session('newsletter_status') }}
                                </div>
                            @endif

                            <p class="small mb-0 mt-2" style="color: rgba(255,255,255,.65);">
                                We send a confirmation link first. Unsubscribe at any time.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

<footer class="site-footer">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="navbar-brand mb-3 d-inline-flex align-items-center" href="{{ route('site.home') }}">
                    @if(\App\Support\Brand::hasLogo())
                        <img src="{{ brand('logo_light') }}" class="brand-logo brand-logo--footer"
                             alt="{{ setting('company.name') }}" width="66" height="66">
                    @else
                        <span class="brand-mark" aria-hidden="true"><strong>WD</strong></span>
                    @endif
                    <span class="brand-text text-white">
                        <strong>{{ setting('company.name') }}</strong>
                        <span>{{ setting('company.slogan') }}</span>
                    </span>
                </a>

                @if($motto = setting('company.motto'))
                    <p class="text-gold fst-italic mb-3" style="font-size:.92rem;">{{ $motto }}</p>
                @endif

                <p class="mb-4">{{ setting('company.about_short') }}</p>

                <div class="social-links">
                    @foreach(['facebook' => 'facebook-f', 'linkedin' => 'linkedin-in', 'twitter' => 'x-twitter', 'instagram' => 'instagram', 'youtube' => 'youtube'] as $key => $icon)
                        @if($url = setting("social.$key"))
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($key) }}">
                                <i class="fa-brands fa-{{ $icon }}"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h5>Divisions</h5>
                <ul class="footer-links">
                    @foreach($navDivisions as $division)
                        <li><a href="{{ route('site.divisions.show', $division->slug) }}">{{ $division->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h5>Company</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('site.about') }}">About Us</a></li>
                    <li><a href="{{ route('site.leadership') }}">Leadership</a></li>
                    <li><a href="{{ route('site.projects.index') }}">Projects</a></li>
                    <li><a href="{{ route('site.blog.index') }}">News</a></li>
                    <li><a href="{{ route('site.careers.index') }}">Careers</a></li>
                    <li><a href="{{ route('site.csr') }}">Responsibility</a></li>
                    <li><a href="{{ route('site.downloads.index') }}">Downloads</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h5>Get in touch</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fa-solid fa-location-dot"></i>
                        <span>
                            {{ setting('contact.address_line1') }}@if(setting('contact.address_line2')), {{ setting('contact.address_line2') }}@endif<br>
                            {{ setting('contact.city') }}, {{ setting('contact.country') }}
                        </span>
                    </li>
                    @if($phone = setting('contact.phone_primary'))
                        <li>
                            <i class="fa-solid fa-phone"></i>
                            <span>
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>
                                @if($alt = setting('contact.phone_secondary'))
                                    <br><a href="tel:{{ preg_replace('/[^\d+]/', '', $alt) }}">{{ $alt }}</a>
                                @endif
                            </span>
                        </li>
                    @endif
                    @if($email = setting('contact.email_general'))
                        <li>
                            <i class="fa-solid fa-envelope"></i>
                            <span><a href="mailto:{{ $email }}">{{ $email }}</a></span>
                        </li>
                    @endif
                    @if($hours = setting('contact.office_hours'))
                        <li>
                            <i class="fa-solid fa-clock"></i>
                            <span>{!! nl2br(e($hours)) !!}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="mb-0">
                    &copy; {{ now()->year }} {{ setting('company.legal_name', setting('company.name')) }}. All rights reserved.
                </p>
                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="{{ route('site.privacy') }}">Privacy Policy</a></li>
                    <li class="list-inline-item"><a href="{{ route('site.terms') }}">Terms of Use</a></li>
                    <li class="list-inline-item"><a href="{{ route('site.contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
