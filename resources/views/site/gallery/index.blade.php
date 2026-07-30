@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Gallery',
        'subtitle' => 'Our teams and equipment at work across Liberia.',
        'breadcrumbs' => ['Gallery' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                @forelse($galleries as $gallery)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ $gallery->url }}" class="card-soft d-block h-100 overflow-hidden text-decoration-none text-reset"
                           data-aos="fade-up" data-aos-delay="{{ $loop->index * 50 }}">
                            @if($gallery->cover_url)
                                <img src="{{ $gallery->cover_url }}" alt="{{ $gallery->title }}"
                                     class="w-100" style="aspect-ratio: 4/3; object-fit: cover;" loading="lazy">
                            @else
                                <div class="d-grid place-items-center bg-light" style="aspect-ratio: 4/3;">
                                    <i class="fa-regular fa-images fa-2x text-primary opacity-25 m-auto"></i>
                                </div>
                            @endif

                            <div class="p-4">
                                @if($gallery->division)
                                    <span class="category-chip mb-2 d-inline-block">{{ $gallery->division->name }}</span>
                                @endif
                                <h2 class="h6 mb-1">{{ $gallery->title }}</h2>
                                <p class="small text-muted mb-0">
                                    {{ $gallery->images->count() }} {{ \Illuminate\Support\Str::plural('photo', $gallery->images->count()) }}
                                </p>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-regular fa-images"></i>
                            <p>No galleries have been published yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

@endsection
