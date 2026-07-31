@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Contact Us',
        'subtitle' => 'Call, email or send us a message — we will route your enquiry to the right division.',
        'breadcrumbs' => ['Contact' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="card-soft p-4 h-100 text-center">
                        <span class="icon-badge mx-auto mb-3"><i class="fa-solid fa-location-dot"></i></span>
                        <h2 class="h6">Visit us</h2>
                        <p class="small text-muted mb-0">
                            {{ setting('contact.address_line1') }}<br>
                            @if(setting('contact.address_line2')){{ setting('contact.address_line2') }}<br>@endif
                            {{ setting('contact.city') }}, {{ setting('contact.country') }}
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card-soft p-4 h-100 text-center">
                        <span class="icon-badge mx-auto mb-3"><i class="fa-solid fa-phone"></i></span>
                        <h2 class="h6">Call us</h2>
                        <p class="small text-muted mb-0">
                            @if($p = setting('contact.phone_primary'))
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $p) }}">{{ $p }}</a><br>
                            @endif
                            @if($s = setting('contact.phone_secondary'))
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $s) }}">{{ $s }}</a>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card-soft p-4 h-100 text-center">
                        <span class="icon-badge mx-auto mb-3"><i class="fa-solid fa-envelope"></i></span>
                        <h2 class="h6">Email us</h2>
                        <p class="small text-muted mb-0">
                            <a href="mailto:{{ setting('contact.email_general') }}">{{ setting('contact.email_general') }}</a><br>
                            <a href="mailto:{{ setting('contact.email_sales') }}">{{ setting('contact.email_sales') }}</a>
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card-soft p-4 h-100 text-center">
                        <span class="icon-badge mx-auto mb-3"><i class="fa-regular fa-clock"></i></span>
                        <h2 class="h6">Office hours</h2>
                        <p class="small text-muted mb-0">{!! nl2br(e(setting('contact.office_hours'))) !!}</p>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-lg-7" id="contact-form">
                    @include('site.partials.auth-required', ['what' => 'send an enquiry'])

                    <div class="form-panel">
                        <h2 class="h4 mb-1">Send us a message</h2>
                        <p class="text-muted small mb-4">
                            Fields marked <span class="required-mark">*</span> are required.
                            For a formal quotation, please use the
                            <a href="{{ route('site.quote.create') }}">Request a Quote</a> form instead.
                        </p>

                        <form method="POST" action="{{ route('site.contact.store') }}" class="row g-3" novalidate>
                            @csrf
                            <div class="d-none" aria-hidden="true">
                                <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                            </div>

                            {{-- Pre-filled from the signed-in account; still editable. --}}
                            <div class="col-md-6">
                                <label class="form-label" for="name">Name <span class="required-mark">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', auth()->user()?->name) }}"
                                       required maxlength="150">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="contact-email">Email <span class="required-mark">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="contact-email" name="email"
                                       value="{{ old('email', auth()->user()?->email) }}" required maxlength="190">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="contact-phone">Phone</label>
                                <input type="tel" class="form-control" id="contact-phone" name="phone"
                                       value="{{ old('phone', auth()->user()?->phone) }}" maxlength="40">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="company">Organisation</label>
                                <input type="text" class="form-control" id="company" name="company"
                                       value="{{ old('company', auth()->user()?->position) }}" maxlength="150">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="department">Which team should see this?</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="general" @selected(old('department') === 'general')>General enquiry</option>
                                    <option value="sales" @selected(old('department') === 'sales')>Sales &amp; business development</option>
                                    <option value="careers" @selected(old('department') === 'careers')>Careers &amp; recruitment</option>
                                    <option value="support" @selected(old('department') === 'support')>Technical support</option>
                                    <option value="accounts" @selected(old('department') === 'accounts')>Accounts &amp; billing</option>
                                </select>
                            </div>

                            {{-- Routing the enquiry to a division means that
                                 division's own team can see and answer it. --}}
                            <div class="col-md-6">
                                <label class="form-label" for="division_id">Which division is this about?</label>
                                <select class="form-select" id="division_id" name="division_id">
                                    <option value="">Not sure / general</option>
                                    @foreach(\App\Models\Division::query()->active()->ordered()->get() as $division)
                                        <option value="{{ $division->id }}" @selected(old('division_id') == $division->id)>
                                            {{ $division->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="subject">Subject <span class="required-mark">*</span></label>
                                <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                       id="subject" name="subject" value="{{ old('subject') }}" required maxlength="200">
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="message">Message <span class="required-mark">*</span></label>
                                <textarea class="form-control @error('message') is-invalid @enderror"
                                          id="message" name="message" rows="6" required
                                          minlength="20" maxlength="5000">{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Send message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    @if($map = setting('contact.map_embed'))
                        <div class="rounded-4 overflow-hidden mb-4" style="height: 340px;">
                            <iframe src="{{ $map }}" width="100%" height="100%" style="border:0;"
                                    allowfullscreen loading="lazy" title="Our location on Google Maps"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    @else
                        <div class="card-soft d-flex flex-column align-items-center justify-content-center text-center p-5 mb-4"
                             style="height: 340px;">
                            <i class="fa-solid fa-map-location-dot fa-2x text-primary opacity-50 mb-3"></i>
                            <p class="small text-muted mb-2">
                                {{ setting('contact.address_line1') }},
                                {{ setting('contact.city') }}, {{ setting('contact.country') }}
                            </p>
                            <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                               href="https://www.google.com/maps/search/?api=1&query={{ setting('contact.latitude') }},{{ setting('contact.longitude') }}">
                                Open in Google Maps
                            </a>
                            <p class="text-muted mt-3 mb-0" style="font-size:.75rem;">
                                An embedded map appears here once a Maps embed URL is set in the CMS.
                            </p>
                        </div>
                    @endif

                    @if($whatsapp = setting('contact.whatsapp'))
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}"
                           target="_blank" rel="noopener noreferrer"
                           class="card-soft p-4 d-flex align-items-center gap-3 text-decoration-none text-reset mb-4">
                            <span class="icon-badge" style="background: rgba(37,211,102,.15); color:#128c46;">
                                <i class="fa-brands fa-whatsapp"></i>
                            </span>
                            <span>
                                <strong class="d-block">Chat on WhatsApp</strong>
                                <small class="text-muted">Quickest route to a reply during office hours</small>
                            </span>
                        </a>
                    @endif

                    @if($faqs->isNotEmpty())
                        <div class="card-soft p-4">
                            <h2 class="h6 mb-3">Common questions</h2>
                            <div class="accordion accordion-flush" id="contactFaqs">
                                @foreach($faqs as $faq)
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button collapsed small px-0" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#cf{{ $faq->id }}">
                                                {{ $faq->question }}
                                            </button>
                                        </h3>
                                        <div id="cf{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#contactFaqs">
                                            <div class="accordion-body px-0 small text-muted">{!! $faq->answer !!}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('site.faqs') }}" class="small fw-semibold">All FAQs <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

@endsection
