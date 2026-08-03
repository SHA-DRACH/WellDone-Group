@extends('portal.layout')

@section('portal-content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-{{ $announcement->category_color }}-subtle text-{{ $announcement->category_color }}">
            {{ ucfirst($announcement->category) }}
        </span>
        <a href="{{ route('portal.announcements') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Back
        </a>
    </div>

    <article class="card-soft p-4">
        <h1 class="h4 mb-2">
            @if($announcement->is_pinned)
                <i class="fa-solid fa-thumbtack text-warning me-1" title="Pinned"></i>
            @endif
            {{ $announcement->title }}
        </h1>

        <p class="small text-muted mb-4 pb-3 border-bottom">
            Published {{ $announcement->published_at?->format('j F Y') }}
            @if($announcement->author) by {{ $announcement->author->name }} @endif
            · For {{ $announcement->audience }}
            @if($announcement->expires_at)
                · Expires {{ $announcement->expires_at->format('j M Y') }}
            @endif
        </p>

        <div class="prose">
            {!! $announcement->body !!}
        </div>

        @if($announcement->attachment_url)
            <div class="mt-4 pt-3 border-top">
                <a href="{{ $announcement->attachment_url }}" class="btn btn-outline-primary btn-sm" download>
                    <i class="fa-solid fa-paperclip me-2"></i>Download the attachment
                </a>
            </div>
        @endif
    </article>

@endsection
