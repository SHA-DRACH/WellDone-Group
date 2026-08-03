@extends('portal.layout')

@section('portal-content')

    <div class="card-soft p-4">
        <h2 class="h6 mb-3">Announcements</h2>

        @forelse($announcements as $announcement)
            <article class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <a href="{{ route('portal.announcements.show', $announcement->slug) }}"
                           class="fw-semibold text-decoration-none">
                            @if($announcement->is_pinned)
                                <i class="fa-solid fa-thumbtack text-warning me-1" title="Pinned"></i>
                            @endif
                            {{ $announcement->title }}
                        </a>
                        <div class="small text-muted">
                            {{ $announcement->published_at?->format('j F Y') }}
                            · {{ $announcement->audience }}
                            @if($announcement->author) · {{ $announcement->author->name }} @endif
                        </div>
                    </div>
                    <span class="badge bg-{{ $announcement->category_color }}-subtle text-{{ $announcement->category_color }}">
                        {{ ucfirst($announcement->category) }}
                    </span>
                </div>

                @if($announcement->excerpt)
                    <p class="small text-muted mt-2 mb-0">{{ $announcement->excerpt }}</p>
                @endif
            </article>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-bullhorn"></i>
                <p class="mb-0">There are no announcements for you right now.</p>
            </div>
        @endforelse

        <div class="mt-4">{{ $announcements->links() }}</div>
    </div>

@endsection
