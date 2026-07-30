@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Projects',
        'subtitle' => 'Completed and ongoing work across our four divisions.',
        'breadcrumbs' => ['Projects' => null],
    ])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.projects.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="filter-division">Division</label>
                    <select class="form-select" id="filter-division" name="division" data-auto-submit>
                        <option value="">All divisions</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->slug }}" @selected(($filters['division'] ?? null) === $division->slug)>
                                {{ $division->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="filter-status">Status</label>
                    <select class="form-select" id="filter-status" name="status" data-auto-submit>
                        <option value="">Any status</option>
                        @foreach(\App\Models\Project::STATUSES as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label" for="filter-q">Search</label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="filter-q" name="q"
                               value="{{ $filters['q'] ?? '' }}" placeholder="Project, client or location">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </div>
            </form>

            <p class="text-muted small mb-4">
                Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} of {{ $projects->total() }} projects
            </p>

            <div class="row g-4">
                @forelse($projects as $project)
                    <div class="col-md-6 col-lg-4">
                        <x-project-card :project="$project" :delay="$loop->index * 50" />
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fa-solid fa-diagram-project"></i>
                            <p>No projects match your filters.</p>
                            <a href="{{ route('site.projects.index') }}" class="btn btn-outline-primary btn-sm">Clear filters</a>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $projects->links() }}
            </div>
        </div>
    </section>

@endsection
