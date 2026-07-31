@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Request a Quote',
        'subtitle' => 'Three short steps. Tell us what you need, describe the scope, and let us know how to reach you.',
        'breadcrumbs' => ['Request a Quote' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @include('site.partials.auth-required', ['what' => 'send a quotation request'])

                    <div class="form-panel">
                        <form method="POST" action="{{ route('site.quote.store') }}"
                              enctype="multipart/form-data" data-quote-wizard novalidate>
                            @csrf
                            <div class="d-none" aria-hidden="true">
                                <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <ol class="wizard-steps list-unstyled">
                                <li class="wizard-step"><span class="wizard-step__num">1</span> Services required</li>
                                <li class="wizard-step"><span class="wizard-step__num">2</span> Project details</li>
                                <li class="wizard-step"><span class="wizard-step__num">3</span> Your contact details</li>
                            </ol>

                            {{-- ---------------------------------------- Step 1 --}}
                            <fieldset class="wizard-pane">
                                <legend class="h5 mb-1">Which services do you need?</legend>
                                <p class="text-muted small mb-4">Select everything that applies — you can pick from more than one division.</p>

                                @error('services')
                                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                                @enderror

                                <div class="accordion service-picker mb-3" id="serviceGroups">
                                    @foreach($divisions as $division)
                                        @continue($division->activeServices->isEmpty())
                                        <div class="accordion-item mb-2">
                                            <h3 class="accordion-header">
                                                <button class="accordion-button {{ $loop->first || $selectedDivision == $division->id ? '' : 'collapsed' }}"
                                                        type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#div{{ $division->id }}">
                                                    <i class="{{ $division->icon }} me-2 text-primary"></i>{{ $division->name }}
                                                </button>
                                            </h3>
                                            <div id="div{{ $division->id }}"
                                                 class="accordion-collapse collapse {{ $loop->first || $selectedDivision == $division->id ? 'show' : '' }}"
                                                 data-bs-parent="#serviceGroups">
                                                <div class="accordion-body">
                                                    @foreach($division->activeServices as $service)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="services[]" value="{{ $service->id }}"
                                                                   id="service{{ $service->id }}"
                                                                   @checked(in_array($service->id, old('services', $selectedService ? [(int) $selectedService] : [])))>
                                                            <label class="form-check-label" for="service{{ $service->id }}">
                                                                <strong>{{ $service->name }}</strong>
                                                                <span class="d-block small text-muted">{{ $service->summary }}</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="division_id">Primary division (optional)</label>
                                    <select class="form-select" id="division_id" name="division_id">
                                        <option value="">Let us decide</option>
                                        @foreach($divisions as $division)
                                            <option value="{{ $division->id }}" @selected(old('division_id', $selectedDivision) == $division->id)>
                                                {{ $division->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </fieldset>

                            {{-- ---------------------------------------- Step 2 --}}
                            <fieldset class="wizard-pane">
                                <legend class="h5 mb-1">Tell us about the project</legend>
                                <p class="text-muted small mb-4">The more detail you give, the more accurate our quotation will be.</p>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="project_title">Project title <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control @error('project_title') is-invalid @enderror"
                                               id="project_title" name="project_title" value="{{ old('project_title') }}"
                                               required maxlength="200" placeholder="e.g. Aggregate supply for county road rehabilitation">
                                        @error('project_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="description">Scope &amp; requirements <span class="required-mark">*</span></label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description" name="description" rows="7" required
                                                  minlength="30" maxlength="8000"
                                                  placeholder="Quantities, specifications, delivery points, standards to be met, and anything else that affects cost or programme.">{{ old('description') }}</textarea>
                                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" for="budget_range">Indicative budget</label>
                                        <select class="form-select" id="budget_range" name="budget_range">
                                            <option value="">Prefer not to say</option>
                                            @foreach(['Under $10,000', '$10,000 – $50,000', '$50,000 – $250,000', '$250,000 – $1m', 'Over $1m'] as $range)
                                                <option value="{{ $range }}" @selected(old('budget_range') === $range)>{{ $range }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label" for="currency">Currency</label>
                                        <select class="form-select" id="currency" name="currency">
                                            @foreach(['USD', 'LRD', 'EUR', 'GBP'] as $currency)
                                                <option value="{{ $currency }}" @selected(old('currency', 'USD') === $currency)>{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="timeline">Required timeline</label>
                                        <select class="form-select" id="timeline" name="timeline">
                                            <option value="">Flexible</option>
                                            @foreach(['Urgent — within 2 weeks', 'Within 1 month', '1 – 3 months', '3 – 6 months', 'Over 6 months'] as $timeline)
                                                <option value="{{ $timeline }}" @selected(old('timeline') === $timeline)>{{ $timeline }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label" for="preferred_start_date">Preferred start</label>
                                        <input type="date" class="form-control @error('preferred_start_date') is-invalid @enderror"
                                               id="preferred_start_date" name="preferred_start_date"
                                               value="{{ old('preferred_start_date') }}" min="{{ now()->toDateString() }}">
                                        @error('preferred_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="delivery_location">Delivery / site location</label>
                                        <input type="text" class="form-control" id="delivery_location" name="delivery_location"
                                               value="{{ old('delivery_location') }}" maxlength="200"
                                               placeholder="e.g. Gbarnga, Bong County">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="attachments">Supporting documents</label>
                                        <input type="file" class="form-control @error('attachments.*') is-invalid @enderror"
                                               id="attachments" name="attachments[]" multiple
                                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                                        <div class="form-text">
                                            Specifications, drawings or bills of quantity. Up to 5 files, 10&nbsp;MB each.
                                        </div>
                                        @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </fieldset>

                            {{-- ---------------------------------------- Step 3 --}}
                            <fieldset class="wizard-pane">
                                <legend class="h5 mb-1">How can we reach you?</legend>
                                <p class="text-muted small mb-4">We will send your reference number to this address immediately.</p>

                                <div class="row g-3">
                                    {{-- Pre-filled from the signed-in account; still editable. --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="contact_name">Your name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control @error('contact_name') is-invalid @enderror"
                                               id="contact_name" name="contact_name"
                                               value="{{ old('contact_name', auth()->user()?->name) }}"
                                               required maxlength="150">
                                        @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="company_name">Organisation</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name"
                                               value="{{ old('company_name', auth()->user()?->position) }}" maxlength="150">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="quote-email">Email <span class="required-mark">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                               id="quote-email" name="email"
                                               value="{{ old('email', auth()->user()?->email) }}" required maxlength="190">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="quote-phone">Phone <span class="required-mark">*</span></label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                               id="quote-phone" name="phone"
                                               value="{{ old('phone', auth()->user()?->phone) }}" required maxlength="40">
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="quote-city">City</label>
                                        <input type="text" class="form-control" id="quote-city" name="city"
                                               value="{{ old('city') }}" maxlength="80">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="quote-country">Country</label>
                                        <input type="text" class="form-control" id="quote-country" name="country"
                                               value="{{ old('country', 'Liberia') }}" maxlength="80">
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input @error('consent') is-invalid @enderror"
                                                   type="checkbox" id="quote-consent" name="consent" value="1"
                                                   required @checked(old('consent'))>
                                            <label class="form-check-label small" for="quote-consent">
                                                I consent to WellDone Group Inc. storing and processing these details in order to
                                                respond to my enquiry. <span class="required-mark">*</span>
                                            </label>
                                            @error('consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            {{-- ---------------------------------------- Navigation --}}
                            <div class="d-flex flex-wrap gap-2 justify-content-between mt-4 pt-4 border-top">
                                <button type="button" class="btn btn-outline-secondary d-none" data-wizard-prev>
                                    <i class="fa-solid fa-arrow-left-long me-2"></i>Back
                                </button>

                                <div class="ms-auto d-flex gap-2">
                                    <button type="button" class="btn btn-primary" data-wizard-next>
                                        Continue<i class="fa-solid fa-arrow-right-long ms-2"></i>
                                    </button>
                                    <button type="submit" class="btn btn-gold btn-lg d-none" data-wizard-submit>
                                        <i class="fa-solid fa-paper-plane me-2"></i>Submit request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <p class="text-center text-muted small mt-4">
                        Prefer to talk? Call
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', setting('contact.phone_primary')) }}">{{ setting('contact.phone_primary') }}</a>
                        or email <a href="mailto:{{ setting('contact.email_sales') }}">{{ setting('contact.email_sales') }}</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>

@endsection
