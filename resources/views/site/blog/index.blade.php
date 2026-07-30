@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'News & Insights',
        'subtitle' => 'Company announcements, project updates and commentary from across the group.',
        'breadcrumbs' => ['News' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="row g-4">
                        @forelse($posts as $post)
                            <div class="col-md-6">
                                <x-post-card :post="$post" :delay="$loop->index * 50" />
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fa-regular fa-newspaper"></i>
                                    <p>No articles match your search.</p>
                                    <a href="{{ route('site.blog.index') }}" class="btn btn-outline-primary btn-sm">Clear filters</a>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-5">{{ $posts->links() }}</div>
                </div>

                <aside class="col-lg-4">
                    <div class="card-soft p-4 mb-4">
                        <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Search</h2>
                        <form method="GET" action="{{ route('site.blog.index') }}">
                            <div class="input-group">
                                <label class="visually-hidden" for="blog-search">Search articles</label>
                                <input type="search" class="form-control" id="blog-search" name="q"
                                       value="{{ $filters['q'] ?? '' }}" placeholder="Search articles">
                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                            </div>
                        </form>
                    </div>

                    @if($categories->isNotEmpty())
                        <div class="card-soft p-4 mb-4">
                            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Categories</h2>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <a href="{{ route('site.blog.index') }}"
                                       class="d-flex justify-content-between text-decoration-none {{ empty($filters['category']) ? 'fw-bold text-primary' : '' }}">
                                        <span>All articles</span>
                                    </a>
                                </li>
                                @foreach($categories as $category)
                                    <li class="mb-2">
                                        <a href="{{ route('site.blog.index', ['category' => $category->slug]) }}"
                                           class="d-flex justify-content-between text-decoration-none {{ ($filters['category'] ?? null) === $category->slug ? 'fw-bold text-primary' : '' }}">
                                            <span>{{ $category->name }}</span>
                                            <span class="badge bg-light text-muted">{{ $category->blogs_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($featured->isNotEmpty())
                        <div class="card-soft p-4 mb-4">
                            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Featured</h2>
                            @foreach($featured as $post)
                                <article class="d-flex gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                    <img src="{{ $post->featured_image_url }}" alt="" loading="lazy"
                                         class="rounded-2 flex-shrink-0" width="72" height="72"
                                         style="object-fit: cover;">
                                    <div>
                                        <a href="{{ $post->url }}" class="small fw-semibold text-decoration-none d-block mb-1">
                                            {{ \Illuminate\Support\Str::limit($post->title, 62) }}
                                        </a>
                                        <span class="text-muted" style="font-size:.78rem;">
                                            {{ $post->published_at?->format('j M Y') }}
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    @if($tags->isNotEmpty())
                        <div class="card-soft p-4">
                            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Topics</h2>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($tags as $tag)
                                    <a href="{{ route('site.blog.index', ['tag' => $tag->slug]) }}"
                                       class="category-chip {{ ($filters['tag'] ?? null) === $tag->slug ? 'bg-primary text-white' : '' }}">
                                        {{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

@endsection
