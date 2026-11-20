@extends('admin.layouts.app')

@section('title', 'Inbox')

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-inbox text-primary"></i>Inbox
    </span>
@endsection

@section('page-subtitle', 'Messages and documents sent to you by colleagues.')

@section('page-actions')
    @if($canSend)
        <a href="{{ route('admin.inbox.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-pen-to-square me-2"></i>New message
        </a>
    @endif
@endsection

@section('content')
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $box === 'inbox' ? 'active' : '' }}" href="{{ route('admin.inbox.index') }}">
                Received @if($unread)<span class="badge bg-danger ms-1">{{ $unread }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $box === 'sent' ? 'active' : '' }}" href="{{ route('admin.inbox.index', ['box' => 'sent']) }}">Sent</a>
        </li>
    </ul>

    <div class="table-card">
        <div class="list-group list-group-flush">
            @forelse($messages as $message)
                @php
                    $other = $box === 'sent' ? $message->recipient : $message->sender;
                    $unreadRow = $box === 'inbox' && ! $message->read_at;
                @endphp
                <a href="{{ route('admin.inbox.show', $message) }}"
                   class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3 {{ $unreadRow ? 'bg-primary-subtle bg-opacity-25' : '' }}">
                    <img src="{{ $other?->avatar_url ?? asset('images/placeholders/avatar.svg') }}" alt="" width="40" height="40"
                         class="rounded-circle flex-shrink-0" style="object-fit: cover;">
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="{{ $unreadRow ? 'fw-bold' : 'fw-semibold' }} text-truncate">
                                {{ $box === 'sent' ? 'To: ' : '' }}{{ $other?->name ?? 'Former staff member' }}
                            </span>
                            <small class="text-muted text-nowrap">{{ $message->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="{{ $unreadRow ? 'fw-semibold' : '' }} text-truncate">
                            {{ $message->subject }}
                            @if($message->hasAttachment())<i class="fa-solid fa-paperclip text-muted ms-1" title="Has a document"></i>@endif
                        </div>
                        <div class="small text-muted text-truncate">{{ \Illuminate\Support\Str::limit($message->body, 140) }}</div>
                    </div>
                </a>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                    {{ $box === 'sent' ? 'You have not sent any messages yet.' : 'No messages yet.' }}
                </div>
            @endforelse
        </div>

        @if($messages->hasPages())
            <div class="p-3 border-top">{{ $messages->links() }}</div>
        @endif
    </div>
@endsection
