@props(['service', 'delay' => 0])

<article class="service-card card-soft h-100 position-relative" data-aos="fade-up" @if($delay) data-aos-delay="{{ $delay }}" @endif>
    <span class="icon-badge"><i class="{{ $service->icon }}" aria-hidden="true"></i></span>

    <h3>
        <a href="{{ $service->url }}" class="stretched-link text-decoration-none text-reset">{{ $service->name }}</a>
    </h3>

    <p>{{ \Illuminate\Support\Str::limit($service->summary, 120) }}</p>
</article>
