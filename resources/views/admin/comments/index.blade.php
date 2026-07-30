@extends('admin.layouts.app')

@section('title', 'Comments')
@section('page-subtitle', 'Reader comments are held here until approved — nothing appears on an article without review.')

@section('content')

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.comments.index') }}"
           class="btn btn-sm {{ $state !== 'approved' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Awaiting review <span class="badge bg-light text-dark ms-1">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('admin.comments.index', ['state' => 'approved']) }}"
           class="btn btn-sm {{ $state === 'approved' ? 'btn-primary' : 'btn-outline-secondary' }}">
            Approved <span class="badge bg-light text-dark ms-1">{{ $approvedCount }}</span>
        </a>
    </div>

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2">
                <input type="hidden" name="state" value="{{ $state }}">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Author or comment text">
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Author</th>
                        <th>Comment</th>
                        <th>Article</th>
                        <th>Received</th>
                        <th class="text-end" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $comment)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $comment->author_name }}</div>
                                <div class="small text-muted">{{ $comment->getAttributes()['author_email'] }}</div>
                            </td>
                            <td style="max-width: 420px;">
                                <div class="small">{{ \Illuminate\Support\Str::limit($comment->body, 220) }}</div>
                            </td>
                            <td class="small">
                                <a href="{{ route('site.blog.show', $comment->blog->slug) }}"
                                   target="_blank" rel="noopener">
                                    {{ \Illuminate\Support\Str::limit($comment->blog->title, 40) }}
                                </a>
                            </td>
                            <td class="small text-muted">{{ $comment->created_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @unless($comment->is_approved)
                                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-outline-success" type="submit"
                                                    data-bs-toggle="tooltip" title="Approve">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endunless

                                    <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                                          data-confirm="Delete this comment permanently?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit"
                                                data-bs-toggle="tooltip" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-regular fa-comments"></i>
                                    <p class="mb-0">
                                        {{ $state === 'approved' ? 'No approved comments yet.' : 'Nothing awaiting review.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $records->links() }}

@endsection
