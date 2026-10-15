@extends('admin.layouts.app')

@section('title', $division->name.' — '.$schema['title'])

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="{{ $schema['icon'] }} text-primary"></i>{{ $schema['title'] }}
    </span>
@endsection

@section('page-subtitle', $division->name.' · '.$schema['intro'])

@section('page-actions')
    <a href="{{ route('admin.division.dashboard', $division->slug) }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
    </a>
    @can('export', App\Models\DivisionRecord::class)
        <a href="{{ route('admin.division.records.export', array_merge(['division' => $division->slug], $filters)) }}"
           class="btn btn-success">
            <i class="fa-solid fa-file-excel me-2"></i>Download Excel
        </a>
    @endcan
    @can('create', App\Models\DivisionRecord::class)
        <a href="{{ route('admin.division.records.create', $division->slug) }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>New {{ $schema['singular'] }}
        </a>
    @endcan
@endsection

@section('content')

    {{-- Totals for whatever is filtered --}}
    @php($totalFields = collect($columns)->filter(fn ($f) => ! empty($f['total'])))
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="metric-card metric-card--accent">
                <div class="metric-card__label">Entries</div>
                <div class="metric-card__value">{{ number_format($count) }}</div>
                <div class="metric-card__hint">{{ ($filters['from'] ?? null) || ($filters['to'] ?? null) ? 'In the selected period' : 'All time' }}</div>
            </div>
        </div>
        @foreach($totalFields->take(4) as $name => $field)
            <div class="col-6 col-lg">
                <div class="metric-card">
                    <div class="metric-card__label">{{ $field['label'] }}</div>
                    <div class="metric-card__value">
                        @if($field['type'] === 'money')
                            @money($totals[$name] ?? 0)
                        @else
                            {{ number_format($totals[$name] ?? 0, $field['type'] === 'integer' ? 0 : 1) }}<small class="text-muted fs-6"> {{ $field['unit'] ?? '' }}</small>
                        @endif
                    </div>
                    <div class="metric-card__hint">Total</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="table-card">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="f-q">Search</label>
                    <input type="search" id="f-q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                           placeholder="Reference, client, item…">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1" for="f-from">From</label>
                    <input type="date" id="f-from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1" for="f-to">To</label>
                    <input type="date" id="f-to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                @foreach(collect($schema['fields'])->where('type', 'select')->take(2) as $name => $field)
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1" for="f-{{ $name }}">{{ $field['label'] }}</label>
                        <select id="f-{{ $name }}" name="field[{{ $name }}]" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($field['options'] as $option)
                                <option value="{{ $option }}" @selected(($filters['field'][$name] ?? null) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-md-auto d-flex gap-2">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply</button>
                    @if(array_filter($filters))
                        <a href="{{ route('admin.division.records.index', $division->slug) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
            <div class="small text-muted mt-2">
                <i class="fa-solid fa-circle-info me-1"></i>The Excel download contains every entry matching these filters, with all fields.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        @foreach($columns as $name => $field)
                            @continue(! $field['list'])
                            <th @class(['text-end' => in_array($field['type'], ['integer', 'number', 'money'], true)])>{{ $field['label'] }}</th>
                        @endforeach
                        <th class="text-end" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $record->reference }}</span>
                                <div class="small text-muted">{{ $record->creator?->name }}</div>
                            </td>
                            <td class="small text-nowrap">{{ $record->record_date->format('j M Y') }}</td>
                            @foreach($columns as $name => $field)
                                @continue(! $field['list'])
                                <td @class(['small', 'text-end text-nowrap' => in_array($field['type'], ['integer', 'number', 'money'], true)])>
                                    @include('admin.division-records._value', ['record' => $record, 'name' => $name, 'field' => $field])
                                </td>
                            @endforeach
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('update', $record)
                                        <a href="{{ route('admin.division.records.edit', [$division->slug, $record]) }}"
                                           class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $record)
                                        <form method="POST" action="{{ route('admin.division.records.destroy', [$division->slug, $record]) }}"
                                              data-confirm="Delete {{ $record->reference }}?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit" data-bs-toggle="tooltip" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + collect($columns)->where('list', true)->count() }}" class="text-center text-muted py-5">
                                <i class="{{ $schema['icon'] }} fa-2x mb-2 d-block opacity-50"></i>
                                No entries {{ array_filter($filters) ? 'match these filters' : 'yet' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">{{ $records->links() }}</div>
        @endif
    </div>

@endsection
