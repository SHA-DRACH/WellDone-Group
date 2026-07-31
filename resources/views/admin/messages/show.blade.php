@extends('admin.layouts.app')

@section('title', 'Enquiry')
@section('page-title', $message->subject)
@section('page-subtitle', 'From '.$message->name.' · '.$message->created_at->format('j F Y, H:i'))

@section('page-actions')
    <a href="{{ route('admin.messages.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to messages
    </a>
@endsection

@section('content')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Message</h2>
                <div>{!! nl2br(e($message->message)) !!}</div>
            </div>

            @if($message->reply_body)
                <div class="card-soft p-4 mb-4" style="border-left: 4px solid var(--wd-blue);">
                    <h2 class="h6 text-uppercase text-muted mb-2" style="letter-spacing:.1em;">
                        Reply sent
                    </h2>
                    <p class="small text-muted mb-3">
                        By {{ $message->repliedBy?->name ?? 'a colleague' }}
                        on {{ $message->replied_at?->format('j F Y, H:i') }}
                    </p>
                    <div>{!! nl2br(e($message->reply_body)) !!}</div>
                </div>
            @endif

            @can('reply', $message)
                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-1" style="letter-spacing:.1em;">
                        {{ $message->reply_body ? 'Send another reply' : 'Reply' }}
                    </h2>
                    <p class="small text-muted mb-3">
                        Emailed to <strong>{{ $message->email }}</strong> and kept on this record.
                    </p>

                    <form method="POST" action="{{ route('admin.messages.reply', $message) }}">
                        @csrf
                        <x-form.textarea name="reply_body" label="Your reply" :rows="7" required
                                         placeholder="Write your response here." />
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-paper-plane me-2"></i>Send reply
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Enquirer</h2>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Name</dt>
                    <dd class="col-7">{{ $message->name }}</dd>
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7"><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>
                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7">{{ $message->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Organisation</dt>
                    <dd class="col-7">{{ $message->company ?: '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Department</dt>
                    <dd class="col-7">{{ ucfirst($message->department ?: 'general') }}</dd>
                </dl>
            </div>

            @can('update', $message)
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Status</h2>
                    <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                        @csrf @method('PATCH')
                        <x-form.select name="status" label="Mark as" :value="$message->status" required
                            :options="collect(\App\Models\ContactMessage::STATUSES)
                                ->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Update status</button>
                    </form>
                </div>
            @endcan

            @can('delete', $message)
                <div class="card-soft p-4">
                    <h2 class="h6 text-danger mb-2">Delete enquiry</h2>
                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                          data-confirm="Permanently delete this enquiry?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fa-solid fa-trash me-2"></i>Delete
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

@endsection
