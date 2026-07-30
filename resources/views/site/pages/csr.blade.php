@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Corporate Social Responsibility',
        'subtitle' => setting('company.csr_statement'),
        'breadcrumbs' => ['About' => route('site.about'), 'Responsibility' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                @forelse($initiatives as $initiative)
                    <div class="col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                        <article class="card-soft h-100 overflow-hidden">
                            <img src="{{ $initiative->image_url }}" alt="{{ $initiative->title }}"
                                 class="w-100" style="aspect-ratio: 16/9; object-fit: cover;" loading="lazy">

                            <div class="p-4">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @if($initiative->focus_area)
                                        <span class="category-chip">{{ $initiative->focus_area }}</span>
                                    @endif
                                    @if($initiative->year)
                                        <span class="small text-muted align-self-center">{{ $initiative->year }}</span>
                                    @endif
                                </div>

                                <h2 class="h5">{{ $initiative->title }}</h2>

                                @if($initiative->location)
                                    <p class="small text-muted mb-2">
                                        <i class="fa-solid fa-location-dot me-1"></i>{{ $initiative->location }}
                                    </p>
                                @endif

                                <p class="text-muted mb-3">{{ $initiative->summary }}</p>

                                @if($initiative->description)
                                    <div class="prose small">{!! $initiative->description !!}</div>
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <p>CSR initiatives have not been published yet.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

@endsection
