@extends('account.layout')

@section('account-content')

    <div class="card-soft p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h6 mb-0">My job applications</h2>
            <a href="{{ route('site.careers.index') }}" class="btn btn-sm btn-primary">Browse vacancies</a>
        </div>

        @forelse($applications as $application)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                <div>
                    @if($application->career)
                        <a href="{{ $application->career->url }}" class="fw-semibold text-decoration-none">
                            {{ $application->career->title }}
                        </a>
                    @else
                        <span class="fw-semibold">General application</span>
                    @endif

                    <div class="small text-muted">
                        {{ $application->reference }} · applied {{ $application->created_at->format('j F Y') }}
                    </div>

                    @if($application->resume_original_name)
                        <div class="small text-muted mt-1">
                            <i class="fa-solid fa-paperclip me-1"></i>{{ $application->resume_original_name }}
                        </div>
                    @endif
                </div>

                <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                    {{ $application->status_label }}
                </span>
            </div>
        @empty
            <div class="empty-state py-4">
                <i class="fa-solid fa-briefcase"></i>
                <p class="mb-2">You have not applied for a vacancy yet.</p>
                <a href="{{ route('site.careers.index') }}" class="btn btn-sm btn-primary">See open roles</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $applications->links() }}</div>
    </div>

    <div class="card-soft p-4 mt-4">
        <h3 class="h6 mb-2">About application statuses</h3>
        <p class="small text-muted mb-0">
            Applications move through <strong>New</strong>, <strong>Reviewing</strong>,
            <strong>Shortlisted</strong>, <strong>Interview</strong> and then an outcome.
            Owing to the volume we receive we cannot always respond individually,
            but the status here is always current.
        </p>
    </div>

@endsection
