@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Downloads',
        'subtitle' => 'Company profile, capability statements, catalogues and technical documents.',
        'breadcrumbs' => ['Downloads' => null],
    ])

    <section class="section">
        <div class="container">
            @forelse($groups as $category => $downloads)
                <div class="{{ $loop->last ? '' : 'mb-5' }}">
                    <h2 class="h4 mb-3">{{ ucwords(str_replace(['_', '-'], ' ', $category)) }}</h2>

                    <div class="row g-4">
                        @foreach($downloads as $download)
                            <div class="col-md-6 col-lg-4">
                                <article class="card-soft h-100 p-4 d-flex flex-column">
                                    <div class="d-flex gap-3 mb-3">
                                        <span class="icon-badge flex-shrink-0">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </span>
                                        <div>
                                            <h3 class="h6 mb-1">{{ $download->title }}</h3>
                                            <p class="small text-muted mb-0">
                                                {{ $download->extension }} · {{ $download->human_size }}
                                                @if($download->division)
                                                    · {{ $download->division->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if($download->description)
                                        <p class="small text-muted">{{ $download->description }}</p>
                                    @endif

                                    <a href="{{ route('site.downloads.get', $download->slug) }}"
                                       class="btn btn-outline-primary btn-sm mt-auto">
                                        <i class="fa-solid fa-download me-2"></i>Download
                                    </a>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No documents are available for download yet.</p>
                    <p class="small">
                        Need something specific? <a href="{{ route('site.contact') }}">Ask us</a> and we will send it over.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

@endsection
