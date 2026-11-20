@extends('admin.layouts.app')

@section('title', $message->subject)

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-envelope-open-text text-primary"></i>{{ $message->subject }}
    </span>
@endsection

@section('page-actions')
    <a href="{{ route('admin.inbox.index', $message->sender_id === auth()->id() ? ['box' => 'sent'] : []) }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Inbox
    </a>
@endsection

@section('content')
    @php($ext = strtolower(pathinfo((string) $message->original_name, PATHINFO_EXTENSION)))

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card-soft p-4 mb-4">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <img src="{{ $message->sender?->avatar_url ?? asset('images/placeholders/avatar.svg') }}" alt="" width="48" height="48"
                         class="rounded-circle" style="object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $message->sender?->name ?? 'Former staff member' }}</div>
                        <div class="small text-muted">
                            {{ $message->sender?->position }}
                            · to {{ $message->recipient?->name }}
                            · {{ $message->created_at->format('d M Y, H:i') }}
                        </div>
                    </div>
                    @if($message->read_at && $message->sender_id === auth()->id())
                        <span class="badge bg-success-subtle text-success" title="{{ $message->read_at->format('d M Y, H:i') }}">
                            <i class="fa-solid fa-check-double me-1"></i>Read
                        </span>
                    @endif
                </div>

                @if($message->replyTo)
                    <div class="small text-muted mb-2">
                        <i class="fa-solid fa-reply me-1"></i>In reply to
                        <a href="{{ route('admin.inbox.show', $message->replyTo) }}">{{ $message->replyTo->subject }}</a>
                    </div>
                @endif

                <div style="white-space: pre-line;">{{ $message->body }}</div>
            </div>

            @if($message->hasAttachment())
                <div class="table-card mb-4">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-2">
                        <div class="small text-truncate">
                            <i class="fa-solid fa-paperclip me-1 text-muted"></i>{{ $message->original_name }}
                            <span class="text-muted">· {{ \App\Support\Format::fileSize((int) $message->file_size) }}</span>
                        </div>
                        <div class="d-flex gap-2">
                            @if($message->previewable)
                                <a href="{{ route('admin.inbox.file', $message) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-eye me-1"></i>View
                                </a>
                            @endif
                            <a href="{{ route('admin.inbox.file', ['message' => $message, 'download' => 1]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-download me-1"></i>Download
                            </a>
                        </div>
                    </div>
                    @if($ext === 'pdf')
                        <iframe src="{{ route('admin.inbox.file', $message) }}" title="{{ $message->original_name }}"
                                style="width:100%; height:70vh; border:0; display:block;"></iframe>
                    @elseif(in_array($ext, ['jpg', 'jpeg', 'png'], true))
                        <div class="p-3 text-center bg-light">
                            <img src="{{ route('admin.inbox.file', $message) }}" alt="" class="img-fluid rounded border">
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-xl-4">
            @can('reply', $message)
                @php($replyErrors = $errors->getBag('reply'))
                <div class="card-soft p-4">
                    <h2 class="h6 mb-3"><i class="fa-solid fa-reply me-2 text-primary"></i>Reply to {{ $message->sender->name }}</h2>
                    <form method="POST" action="{{ route('admin.inbox.reply', $message) }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        @if($replyErrors->any())
                            <div class="alert alert-danger small py-2">
                                @foreach($replyErrors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif
                        <textarea name="body" rows="6" maxlength="10000" required
                                  class="form-control form-control-sm mb-2 {{ $replyErrors->has('body') ? 'is-invalid' : '' }}"
                                  placeholder="Write your reply…">{{ old('body') }}</textarea>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip"
                               class="form-control form-control-sm mb-1">
                        <div class="form-text mb-3">Optional document · up to 20 MB</div>
                        <button class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-paper-plane me-1"></i>Send reply</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
@endsection
