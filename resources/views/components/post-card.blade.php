@props(['post', 'delay' => 0])

<article class="post-card card-soft" data-aos="fade-up" @if($delay) data-aos-delay="{{ $delay }}" @endif>
    <div class="post-card__media">
        <img src="{{ $post->featured_image_url }}"
             alt="{{ $post->featured_image_alt ?: $post->title }}"
             loading="lazy" decoding="async" width="640" height="360">
    </div>

    <div class="post-card__body">
        <div class="post-meta">
            @if($post->category)
                <a class="category-chip" href="{{ route('site.blog.index', ['category' => $post->category->slug]) }}">
                    {{ $post->category->name }}
                </a>
            @endif
            <span><i class="fa-regular fa-calendar me-1"></i>{{ $post->published_at?->format('j M Y') }}</span>
            <span><i class="fa-regular fa-clock me-1"></i>{{ $post->reading_time }} min read</span>
        </div>

        <h3><a href="{{ $post->url }}">{{ $post->title }}</a></h3>

        <p class="mb-3">{{ \Illuminate\Support\Str::limit($post->excerpt, 140) }}</p>

        <a class="fw-semibold mt-auto align-self-start" href="{{ $post->url }}">
            Read article <i class="fa-solid fa-arrow-right-long ms-1"></i>
        </a>
    </div>
</article>
