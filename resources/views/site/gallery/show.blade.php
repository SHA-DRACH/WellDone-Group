@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => $gallery->title,
        'subtitle' => $gallery->description,
        'breadcrumbs' => ['Gallery' => route('site.gallery.index'), $gallery->title => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-3">
                @forelse($gallery->images as $image)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ $image->url }}" target="_blank" rel="noopener noreferrer"
                           class="d-block rounded-3 overflow-hidden" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 8) * 40 }}">
                            <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $gallery->title }}"
                                 class="w-100" style="aspect-ratio: 4/3; object-fit: cover;" loading="lazy" decoding="async">
                        </a>
                        @if($image->caption)
                            <p class="small text-muted mt-2 mb-0">{{ $image->caption }}</p>
                        @endif
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-regular fa-image"></i>
                            <p>No photographs have been added to this gallery yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @if($others->isNotEmpty())
        <section class="section section--surface section--tight">
            <div class="container">
                <h2 class="h5 mb-4">Other galleries</h2>
                <div class="row g-3">
                    @foreach($others as $other)
                        <div class="col-6 col-lg-3">
                            <a href="{{ $other->url }}" class="card-soft d-block p-3 text-decoration-none text-reset">
                                <strong class="small d-block">{{ $other->title }}</strong>
                                <span class="text-muted" style="font-size:.78rem;">
                                    {{ $other->images_count ?? '' }} View gallery
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
