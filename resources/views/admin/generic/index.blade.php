{{--
    Generic list screen.

    Driven by $columns from the controller, so the ~15 structurally identical
    modules (sliders, partners, testimonials, milestones …) need no bespoke
    view. Modules with genuinely different needs ship their own index.

    $columns: [ key => ['label' => …, 'type' => text|image|badge|boolean|date|link, …] ]
--}}
@extends('admin.layouts.app')

@section('title', $moduleTitle)
@section('page-subtitle', $moduleSubtitle ?? '')

@section('page-actions')
    @can('create', $modelClass)
        <a href="{{ route("admin.$routeName.create") }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>New {{ $moduleSingular }}
        </a>
    @endcan
@endsection

@section('content')

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Search {{ strtolower($moduleTitle) }}">
                    </div>
                </div>

                @foreach($filters ?? [] as $filter)
                    <div class="col-md-3">
                        <select name="{{ $filter['name'] }}" class="form-select form-select-sm" data-auto-submit>
                            <option value="">{{ $filter['placeholder'] }}</option>
                            @foreach($filter['options'] as $value => $label)
                                <option value="{{ $value }}" @selected(request($filter['name']) == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    @if($search || collect($filters ?? [])->contains(fn ($f) => request()->filled($f['name'])))
                        <a href="{{ route("admin.$routeName.index") }}" class="btn btn-sm btn-link text-muted">Clear</a>
                    @endif
                </div>

                <div class="col-md-auto ms-auto text-md-end">
                    <span class="small text-muted">{{ $records->total() }} total</span>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        @foreach($columns as $key => $column)
                            <th @if(($column['align'] ?? null) === 'end') class="text-end" @endif>
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($records as $record)
                        <tr>
                            @foreach($columns as $key => $column)
                                <td @if(($column['align'] ?? null) === 'end') class="text-end" @endif>
                                    @include('admin.generic.cell', ['record' => $record, 'key' => $key, 'column' => $column])
                                </td>
                            @endforeach

                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('update', $record)
                                        <a href="{{ route("admin.$routeName.edit", $record) }}"
                                           class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan

                                    @can('delete', $record)
                                        <form method="POST" action="{{ route("admin.$routeName.destroy", $record) }}"
                                              data-confirm="Delete this {{ strtolower($moduleSingular) }}? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger"
                                                    data-bs-toggle="tooltip" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}">
                                <div class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <p class="mb-2">No {{ strtolower($moduleTitle) }} found.</p>
                                    @can('create', $modelClass)
                                        <a href="{{ route("admin.$routeName.create") }}" class="btn btn-sm btn-primary">
                                            Add the first one
                                        </a>
                                    @endcan
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
