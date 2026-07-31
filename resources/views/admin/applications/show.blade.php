@extends('admin.layouts.app')

@section('title', 'Application '.$application->reference)
@section('page-title', $application->full_name)
@section('page-subtitle', $application->reference.' · applied '.$application->created_at->format('j F Y'))

@section('page-actions')
    <a href="{{ route('admin.applications.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to applications
    </a>

    @can('downloadResume', $application)
        <a href="{{ route('admin.applications.resume', $application) }}" class="btn btn-primary">
            <i class="fa-solid fa-file-arrow-down me-2"></i>Download résumé
        </a>
    @endcan
@endsection

@section('content')

    <div class="row g-4">
        <div class="col-lg-8">
            @if($application->cover_letter)
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Cover letter</h2>
                    <div>{!! nl2br(e($application->cover_letter)) !!}</div>
                </div>
            @endif

            @can('update', $application)
                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Review</h2>

                    <form method="POST" action="{{ route('admin.applications.update', $application) }}">
                        @csrf @method('PATCH')

                        <x-form.select name="status" label="Status" :value="$application->status" required
                            :options="collect(\App\Models\JobApplication::STATUSES)
                                ->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />

                        <x-form.textarea name="internal_notes" label="Internal notes"
                                         :value="$application->internal_notes" :rows="5"
                                         help="Interview notes and assessment. Never shown to the candidate." />

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Candidate</h2>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></dd>

                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7">
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $application->phone) }}">{{ $application->phone }}</a>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Location</dt>
                    <dd class="col-7">
                        {{ collect([$application->city, $application->country])->filter()->implode(', ') ?: '—' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Experience</dt>
                    <dd class="col-7">
                        {{ $application->years_experience !== null ? $application->years_experience.' years' : '—' }}
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Qualification</dt>
                    <dd class="col-7">{{ $application->highest_qualification ?: '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Expected salary</dt>
                    <dd class="col-7">{{ $application->expected_salary ?: '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Available from</dt>
                    <dd class="col-7">{{ $application->available_from?->format('j M Y') ?: '—' }}</dd>

                    @if($application->linkedin_url)
                        <dt class="col-5 text-muted fw-normal">LinkedIn</dt>
                        <dd class="col-7">
                            <a href="{{ $application->linkedin_url }}" target="_blank" rel="noopener">Profile</a>
                        </dd>
                    @endif

                    @if($application->portfolio_url)
                        <dt class="col-5 text-muted fw-normal">Portfolio</dt>
                        <dd class="col-7">
                            <a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener">Website</a>
                        </dd>
                    @endif
                </dl>
            </div>

            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Vacancy</h2>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Position</dt>
                    <dd class="col-7">{{ $application->career?->title ?? 'General application' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Division</dt>
                    <dd class="col-7">{{ $application->career?->division?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }}">
                            {{ $application->status_label }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted fw-normal">Reviewed by</dt>
                    <dd class="col-7">{{ $application->reviewer?->name ?? 'Not yet reviewed' }}</dd>
                </dl>
            </div>

            @can('delete', $application)
                <div class="card-soft p-4">
                    <h2 class="h6 text-danger mb-2">Delete application</h2>
                    <p class="small text-muted">
                        Removes the record and the candidate's résumé permanently.
                    </p>
                    <form method="POST" action="{{ route('admin.applications.destroy', $application) }}"
                          data-confirm="Permanently delete this application and its résumé?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fa-solid fa-trash me-2"></i>Delete permanently
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

@endsection
