@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => $career->title,
        'subtitle' => collect([$career->division?->name, $career->location, $career->employment_type_label])->filter()->implode(' · '),
        'breadcrumbs' => [
            'Careers' => route('site.careers.index'),
            \Illuminate\Support\Str::limit($career->title, 42) => null,
        ],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    @if(session('application_reference'))
                        <div class="alert alert-success">
                            <h2 class="h6 mb-1"><i class="fa-solid fa-circle-check me-2"></i>Application received</h2>
                            <p class="mb-0 small">
                                Your reference is <strong>{{ session('application_reference') }}</strong>.
                                We have emailed you a confirmation.
                            </p>
                        </div>
                    @endif

                    <div class="prose">
                        {!! $career->description !!}
                    </div>

                    @if($career->responsibilities)
                        <h2 class="h4 mt-5 mb-3">Key responsibilities</h2>
                        <div class="prose">{!! $career->responsibilities !!}</div>
                    @endif

                    @if($career->requirements)
                        <h2 class="h4 mt-5 mb-3">Requirements</h2>
                        <div class="prose">{!! $career->requirements !!}</div>
                    @endif

                    @if($career->benefits)
                        <h2 class="h4 mt-5 mb-3">What we offer</h2>
                        <div class="prose">{!! $career->benefits !!}</div>
                    @endif

                    {{-- Application form --}}
                    <div id="apply" class="form-panel mt-5">
                        <h2 class="h4 mb-1">Apply for this role</h2>
                        <p class="text-muted small mb-4">
                            Fields marked <span class="required-mark">*</span> are required.
                            Your résumé must be a PDF or Word document, no larger than 5&nbsp;MB.
                        </p>

                        @if(! $career->is_open)
                            <div class="alert alert-warning mb-0">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                This vacancy is closed and is no longer accepting applications.
                            </div>
                        @else
                            <form method="POST" action="{{ route('site.careers.apply', $career->slug) }}"
                                  enctype="multipart/form-data" class="row g-3" novalidate>
                                @csrf
                                <div class="d-none" aria-hidden="true">
                                    <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="full_name">Full name <span class="required-mark">*</span></label>
                                    <input type="text" class="form-control @error('full_name') is-invalid @enderror"
                                           id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="150">
                                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email <span class="required-mark">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}" required maxlength="190">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone <span class="required-mark">*</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone') }}" required maxlength="40">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="city">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" maxlength="80">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label" for="country">Country</label>
                                    <input type="text" class="form-control" id="country" name="country"
                                           value="{{ old('country', 'Liberia') }}" maxlength="80">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="years_experience">Years of experience</label>
                                    <input type="number" class="form-control @error('years_experience') is-invalid @enderror"
                                           id="years_experience" name="years_experience" value="{{ old('years_experience') }}"
                                           min="0" max="60">
                                    @error('years_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="highest_qualification">Highest qualification</label>
                                    <input type="text" class="form-control" id="highest_qualification"
                                           name="highest_qualification" value="{{ old('highest_qualification') }}" maxlength="150">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="available_from">Available from</label>
                                    <input type="date" class="form-control @error('available_from') is-invalid @enderror"
                                           id="available_from" name="available_from" value="{{ old('available_from') }}"
                                           min="{{ now()->toDateString() }}">
                                    @error('available_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="linkedin_url">LinkedIn profile</label>
                                    <input type="url" class="form-control @error('linkedin_url') is-invalid @enderror"
                                           id="linkedin_url" name="linkedin_url" value="{{ old('linkedin_url') }}"
                                           placeholder="https://linkedin.com/in/...">
                                    @error('linkedin_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="portfolio_url">Portfolio / website</label>
                                    <input type="url" class="form-control @error('portfolio_url') is-invalid @enderror"
                                           id="portfolio_url" name="portfolio_url" value="{{ old('portfolio_url') }}">
                                    @error('portfolio_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="expected_salary">Expected salary</label>
                                    <input type="text" class="form-control" id="expected_salary"
                                           name="expected_salary" value="{{ old('expected_salary') }}" maxlength="80">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="resume">Résumé / CV <span class="required-mark">*</span></label>
                                    <input type="file" class="form-control @error('resume') is-invalid @enderror"
                                           id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                    @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="cover_letter">Cover letter</label>
                                    <textarea class="form-control @error('cover_letter') is-invalid @enderror"
                                              id="cover_letter" name="cover_letter" rows="6"
                                              maxlength="5000" placeholder="Tell us why you are a good fit for this role.">{{ old('cover_letter') }}</textarea>
                                    @error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input @error('consent') is-invalid @enderror"
                                               type="checkbox" id="consent" name="consent" value="1" required @checked(old('consent'))>
                                        <label class="form-check-label small" for="consent">
                                            I consent to WellDone Group Inc. storing and processing the information in this
                                            application for recruitment purposes. <span class="required-mark">*</span>
                                        </label>
                                        @error('consent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa-solid fa-paper-plane me-2"></i>Submit application
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                <aside class="col-lg-4">
                    <div class="card-soft p-4 mb-4 position-sticky" style="top: 100px;">
                        <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Role summary</h2>
                        <dl class="row small mb-4">
                            @if($career->division)
                                <dt class="col-5 text-muted fw-normal">Division</dt>
                                <dd class="col-7">{{ $career->division->name }}</dd>
                            @endif
                            @if($career->department)
                                <dt class="col-5 text-muted fw-normal">Department</dt>
                                <dd class="col-7">{{ $career->department }}</dd>
                            @endif
                            @if($career->location)
                                <dt class="col-5 text-muted fw-normal">Location</dt>
                                <dd class="col-7">{{ $career->location }}</dd>
                            @endif
                            <dt class="col-5 text-muted fw-normal">Type</dt>
                            <dd class="col-7">{{ $career->employment_type_label }}</dd>
                            <dt class="col-5 text-muted fw-normal">Level</dt>
                            <dd class="col-7">{{ $career->experience_level_label }}</dd>
                            <dt class="col-5 text-muted fw-normal">Positions</dt>
                            <dd class="col-7">{{ $career->positions_available }}</dd>
                            @if($career->salary_range)
                                <dt class="col-5 text-muted fw-normal">Salary</dt>
                                <dd class="col-7">{{ $career->salary_range }}</dd>
                            @endif
                            @if($career->closes_at)
                                <dt class="col-5 text-muted fw-normal">Closes</dt>
                                <dd class="col-7">{{ $career->closes_at->format('j F Y') }}</dd>
                            @endif
                        </dl>

                        @if($career->is_open)
                            <a href="#apply" class="btn btn-primary w-100">Apply now</a>
                        @else
                            <span class="btn btn-secondary w-100 disabled">Applications closed</span>
                        @endif
                    </div>

                    @if($others->isNotEmpty())
                        <div class="card-soft p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Other openings</h2>
                            <ul class="list-unstyled mb-0">
                                @foreach($others as $other)
                                    <li class="{{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                                        <a href="{{ $other->url }}" class="small fw-semibold text-decoration-none">{{ $other->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

@endsection
