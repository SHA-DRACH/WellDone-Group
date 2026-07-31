@extends('account.layout')

@section('account-content')

    <div class="card-soft p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">My enquiries</h2>
            <a href="{{ route('site.contact') }}" class="btn btn-sm btn-primary">Send an enquiry</a>
        </div>

        @forelse($messages as $message)
            <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <span class="fw-semibold">{{ $message->subject }}</span>
                        <div class="small text-muted">
                            Sent {{ $message->created_at->format('j F Y') }}
                            · {{ ucfirst($message->department ?: 'general') }}
                        </div>
                    </div>
                    <span class="badge bg-{{ $message->status_color }}-subtle text-{{ $message->status_color }}">
                        {{ ucfirst($message->status) }}
                    </span>
                </div>

                <p class="small text-muted mt-2 mb-0">
                    {{ \Illuminate\Support\Str::limit($message->message, 220) }}
                </p>

                @if($message->reply_body)
                    <div class="mt-3 p-3 rounded-3" style="background: var(--wd-surface); border-left: 3px solid var(--wd-blue);">
                        <div class="small fw-semibold mb-1">
                            <i class="fa-solid fa-reply me-1 text-primary"></i>Our reply
                            <span class="text-muted fw-normal">
                                — {{ $message->replied_at?->format('j M Y') }}
                            </span>
                        </div>
                        <div class="small">{!! nl2br(e($message->reply_body)) !!}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-envelope"></i>
                <p class="mb-2">You have not sent us an enquiry yet.</p>
                <a href="{{ route('site.contact') }}" class="btn btn-sm btn-primary">Get in touch</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $messages->links() }}</div>
    </div>

@endsection
