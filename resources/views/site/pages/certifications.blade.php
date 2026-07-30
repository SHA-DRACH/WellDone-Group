@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Certifications & Accreditations',
        'subtitle' => 'Registrations, licences and permits held by WellDone Group Inc.',
        'breadcrumbs' => ['About' => route('site.about'), 'Certifications' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                @forelse($certificates as $certificate)
                    <div class="col-md-6 col-lg-4">
                        <article class="card-soft h-100 p-4" data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                            <div class="d-flex gap-3 mb-3">
                                <span class="icon-badge icon-badge--gold flex-shrink-0">
                                    <i class="fa-solid fa-certificate"></i>
                                </span>
                                <div>
                                    <h2 class="h6 mb-1">{{ $certificate->title }}</h2>
                                    <p class="small text-muted mb-0">{{ $certificate->issuer }}</p>
                                </div>
                            </div>

                            @if($certificate->description)
                                <p class="small text-muted">{{ $certificate->description }}</p>
                            @endif

                            <dl class="row small mb-0">
                                @if($certificate->reference_number)
                                    <dt class="col-6 text-muted fw-normal">Reference</dt>
                                    <dd class="col-6 text-end">{{ $certificate->reference_number }}</dd>
                                @endif
                                @if($certificate->issued_at)
                                    <dt class="col-6 text-muted fw-normal">Issued</dt>
                                    <dd class="col-6 text-end">{{ $certificate->issued_at->format('M Y') }}</dd>
                                @endif
                                @if($certificate->expires_at)
                                    <dt class="col-6 text-muted fw-normal">Valid until</dt>
                                    <dd class="col-6 text-end {{ $certificate->is_expired ? 'text-danger' : '' }}">
                                        {{ $certificate->expires_at->format('M Y') }}
                                    </dd>
                                @endif
                            </dl>

                            @if($certificate->file_url)
                                <a href="{{ $certificate->file_url }}" class="btn btn-sm btn-outline-primary w-100 mt-3" download>
                                    <i class="fa-solid fa-file-arrow-down me-2"></i>View certificate
                                </a>
                            @endif
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-solid fa-certificate"></i>
                            <p>Certificates have not been published yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

@endsection
