@props(['testimonial'])

<figure class="testimonial-card h-100 mb-0">
    <div class="quote-mark" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></div>

    @if($testimonial->rating)
        <div class="mb-3" aria-label="{{ $testimonial->rating }} out of 5">
            @for($i = 1; $i <= 5; $i++)
                <i class="fa-{{ $i <= $testimonial->rating ? 'solid' : 'regular' }} fa-star text-gold small"></i>
            @endfor
        </div>
    @endif

    <blockquote class="mb-4">{{ $testimonial->quote }}</blockquote>

    <figcaption class="testimonial-author">
        @if($testimonial->avatar)
            <img src="{{ $testimonial->avatar_url }}" alt="" loading="lazy" width="52" height="52">
        @else
            <span class="avatar-fallback" aria-hidden="true">{{ $testimonial->initials }}</span>
        @endif
        <span>
            <strong>{{ $testimonial->author_name }}</strong>
            <small>{{ collect([$testimonial->author_title, $testimonial->company])->filter()->implode(', ') }}</small>
        </span>
    </figcaption>
</figure>
