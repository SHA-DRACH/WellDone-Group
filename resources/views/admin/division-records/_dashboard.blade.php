{{--
    The operations log on a division dashboard: an entry form that is always
    open, this month's totals, the latest entries and the Excel download.

    Expects: $division, $recordSchema, $recentRecords, $monthTotals, $monthCount.
--}}
@php
    $columns = \App\Support\DivisionRecords\RecordSchema::columns($recordSchema);
    $listed = collect($columns)->where('list', true)->take(5);
@endphp

<div class="quick-entry mb-4" id="operations-log">
    <div class="quick-entry__head">
        <div>
            <h2 class="h6 mb-0"><i class="{{ $recordSchema['icon'] }} text-primary me-2"></i>{{ $recordSchema['title'] }}</h2>
            <small class="text-muted">{{ $recordSchema['intro'] }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.division.records.index', $division->slug) }}" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-table-list me-1"></i>Full log
            </a>
            @can('export', App\Models\DivisionRecord::class)
                <a href="{{ route('admin.division.records.export', $division->slug) }}" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-file-excel me-1"></i>Download Excel
                </a>
                <a href="{{ route('admin.division.records.export', ['division' => $division->slug, 'from' => now()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]) }}"
                   class="btn btn-sm btn-outline-success">
                    This month
                </a>
            @endcan
        </div>
    </div>

    <div class="quick-entry__body">
        {{-- This month at a glance --}}
        <div class="d-flex flex-wrap gap-4 mb-3 small">
            <div><span class="text-muted">This month:</span> <strong>{{ number_format($monthCount) }}</strong> {{ \Illuminate\Support\Str::plural($recordSchema['singular'], $monthCount) }}</div>
            @foreach($monthTotals as $name => $total)
                @php($field = $columns[$name])
                <div>
                    <span class="text-muted">{{ $field['label'] }}:</span>
                    <strong>
                        @if($field['type'] === 'money') @money($total) @else {{ number_format($total, $field['type'] === 'integer' ? 0 : 1) }} {{ $field['unit'] ?? '' }} @endif
                    </strong>
                </div>
            @endforeach
        </div>

        @can('create', App\Models\DivisionRecord::class)
            <form method="POST" action="{{ route('admin.division.records.store', $division->slug) }}" novalidate
                  class="border rounded-3 p-3 bg-white mb-3">
                @csrf
                <input type="hidden" name="return" value="dashboard">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">Add a {{ $recordSchema['singular'] }}</h3>
                    <span class="small text-muted">Saved straight into the log and the Excel download.</span>
                </div>

                @include('admin.division-records._fields', ['schema' => $recordSchema, 'record' => new \App\Models\DivisionRecord, 'idPrefix' => 'quick'])

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save {{ $recordSchema['singular'] }}
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">Clear</button>
                </div>
            </form>
        @endcan

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ref.</th>
                        <th>Date</th>
                        @foreach($listed as $name => $field)
                            <th @class(['text-end' => in_array($field['type'], ['integer', 'number', 'money'], true)])>{{ $field['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRecords as $record)
                        <tr>
                            <td class="small fw-semibold text-nowrap">
                                @can('update', $record)
                                    <a href="{{ route('admin.division.records.edit', [$division->slug, $record]) }}">{{ $record->reference }}</a>
                                @else
                                    {{ $record->reference }}
                                @endcan
                            </td>
                            <td class="small text-nowrap">{{ $record->record_date->format('j M') }}</td>
                            @foreach($listed as $name => $field)
                                <td @class(['small', 'text-end text-nowrap' => in_array($field['type'], ['integer', 'number', 'money'], true)])>
                                    @include('admin.division-records._value', ['record' => $record, 'name' => $name, 'field' => $field])
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $listed->count() }}" class="text-center text-muted small py-4">
                                Nothing logged yet — the first entry above starts the record.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
