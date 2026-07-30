@props(['project', 'delay' => 0])

<article class="project-card card-soft" data-aos="fade-up" @if($delay) data-aos-delay="{{ $delay }}" @endif>
    <div class="project-card__media">
        <img src="{{ $project->featured_image_url }}"
             alt="{{ $project->title }}"
             loading="lazy" decoding="async" width="640" height="480">
    </div>

    <div class="project-card__body">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="status-badge status-badge--{{ $project->status }}">{{ $project->status_label }}</span>
            @if($project->division)
                <span class="small text-muted">{{ $project->division->name }}</span>
            @endif
        </div>

        <h3><a href="{{ $project->url }}" class="stretched-link text-decoration-none text-reset">{{ $project->title }}</a></h3>

        <p class="project-meta mb-2">
            @if($project->location)
                <i class="fa-solid fa-location-dot me-1"></i>{{ $project->location }}
            @endif
            @if($project->duration_label)
                <span class="ms-2"><i class="fa-regular fa-calendar me-1"></i>{{ $project->duration_label }}</span>
            @endif
        </p>

        @if($project->summary)
            <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($project->summary, 130) }}</p>
        @endif
    </div>
</article>
