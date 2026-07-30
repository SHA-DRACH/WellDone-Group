@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => $blog->title,
        'breadcrumbs' => [
            'News' => route('site.blog.index'),
            \Illuminate\Support\Str::limit($blog->title, 48) => null,
        ],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <article class="col-lg-8">
                    <div class="d-flex flex-wrap gap-3 align-items-center mb-4 small text-muted">
                        @if($blog->category)
                            <a class="category-chip" href="{{ route('site.blog.index', ['category' => $blog->category->slug]) }}">
                                {{ $blog->category->name }}
                            </a>
                        @endif
                        <span><i class="fa-regular fa-calendar me-1"></i>{{ $blog->published_at?->format('j F Y') }}</span>
                        <span><i class="fa-regular fa-clock me-1"></i>{{ $blog->reading_time }} min read</span>
                        @if($blog->author)
                            <span><i class="fa-regular fa-user me-1"></i>{{ $blog->author->name }}</span>
                        @endif
                        <span><i class="fa-regular fa-eye me-1"></i>{{ number_format($blog->views) }} views</span>
                    </div>

                    <img src="{{ $blog->featured_image_url }}" class="w-100 rounded-4 mb-4"
                         alt="{{ $blog->featured_image_alt ?: $blog->title }}">

                    <div class="prose">
                        {!! $blog->body !!}
                    </div>

                    @if($blog->tags->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-4 pt-4 border-top">
                            <span class="small text-muted me-1">Topics:</span>
                            @foreach($blog->tags as $tag)
                                <a class="category-chip" href="{{ route('site.blog.index', ['tag' => $tag->slug]) }}">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Social sharing --}}
                    @php($shareUrl = urlencode(url()->current()))
                    @php($shareText = urlencode($blog->title))
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-4">
                        <span class="small text-muted me-1">Share:</span>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                           href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" aria-label="Share on LinkedIn">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                           href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" aria-label="Share on Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                           href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" aria-label="Share on X">
                            <i class="fa-brands fa-x-twitter"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer"
                           href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" aria-label="Share on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-primary"
                           href="mailto:?subject={{ $shareText }}&body={{ $shareUrl }}" aria-label="Share by email">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                    </div>

                    {{-- Comments --}}
                    @if($blog->allow_comments && setting('system.enable_blog_comments', true))
                        <div id="comments" class="mt-5 pt-4 border-top">
                            <h2 class="h4 mb-4">
                                Comments
                                <span class="text-muted fw-normal">({{ $blog->approvedComments->count() }})</span>
                            </h2>

                            @forelse($blog->approvedComments as $comment)
                                <div class="card-soft p-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong>{{ $comment->author_name }}</strong>
                                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-0">{{ $comment->body }}</p>

                                    @foreach($comment->replies as $reply)
                                        <div class="mt-3 ps-4 border-start">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong class="small">{{ $reply->author_name }}</strong>
                                                <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                            </div>
                                            <p class="mb-0 small">{{ $reply->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-muted">No comments yet. Be the first to share your thoughts.</p>
                            @endforelse

                            <div class="form-panel mt-4">
                                <h3 class="h5 mb-3">Leave a comment</h3>
                                <p class="small text-muted">
                                    Comments are reviewed before they appear. Your email address is not published.
                                </p>

                                <form method="POST" action="{{ route('site.blog.comments.store', $blog->slug) }}" class="row g-3">
                                    @csrf
                                    <div class="d-none" aria-hidden="true">
                                        <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="author_name">Name <span class="required-mark">*</span></label>
                                        <input type="text" class="form-control @error('author_name') is-invalid @enderror"
                                               id="author_name" name="author_name" value="{{ old('author_name') }}" required maxlength="120">
                                        @error('author_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="author_email">Email <span class="required-mark">*</span></label>
                                        <input type="email" class="form-control @error('author_email') is-invalid @enderror"
                                               id="author_email" name="author_email" value="{{ old('author_email') }}" required maxlength="190">
                                        @error('author_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="body">Comment <span class="required-mark">*</span></label>
                                        <textarea class="form-control @error('body') is-invalid @enderror"
                                                  id="body" name="body" rows="4" required minlength="10" maxlength="2000">{{ old('body') }}</textarea>
                                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Submit comment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </article>

                <aside class="col-lg-4">
                    <div class="card-soft p-4 mb-4 position-sticky" style="top: 100px;">
                        <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Related articles</h2>

                        @forelse($related as $post)
                            <article class="d-flex gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                <img src="{{ $post->featured_image_url }}" alt="" loading="lazy"
                                     class="rounded-2 flex-shrink-0" width="72" height="72" style="object-fit: cover;">
                                <div>
                                    <a href="{{ $post->url }}" class="small fw-semibold text-decoration-none d-block mb-1">
                                        {{ \Illuminate\Support\Str::limit($post->title, 62) }}
                                    </a>
                                    <span class="text-muted" style="font-size:.78rem;">{{ $post->published_at?->format('j M Y') }}</span>
                                </div>
                            </article>
                        @empty
                            <p class="small text-muted mb-0">No related articles yet.</p>
                        @endforelse

                        <a href="{{ route('site.blog.index') }}" class="btn btn-outline-primary btn-sm w-100 mt-3">
                            All articles
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

@endsection
