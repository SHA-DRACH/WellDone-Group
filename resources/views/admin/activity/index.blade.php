@extends('admin.layouts.app')

@section('title', 'Activity Log')
@section('page-subtitle', 'Audit trail of changes made through the control panel. Read-only.')

@section('content')

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Search descriptions">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="log" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All modules</option>
                        @foreach($logs as $log)
                            <option value="{{ $log }}" @selected(request('log') === $log)>
                                {{ ucwords(str_replace('-', ' ', $log)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="event" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ ucfirst($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="causer" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Anyone</option>
                        @foreach($causers as $id => $name)
                            <option value="{{ $id }}" @selected(request('causer') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Module</th>
                        <th>Event</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $activity)
                        <tr>
                            <td class="small text-muted" style="white-space: nowrap;">
                                <span data-bs-toggle="tooltip" title="{{ $activity->created_at->format('j M Y, H:i:s') }}">
                                    {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="small">{{ $activity->causer?->name ?? 'System' }}</td>
                            <td class="small">{{ ucwords(str_replace('-', ' ', $activity->log_name ?: '—')) }}</td>
                            <td>
                                @php($colour = match($activity->event) {
                                    'created' => 'success',
                                    'updated' => 'info',
                                    'deleted' => 'danger',
                                    default => 'secondary',
                                })
                                <span class="badge bg-{{ $colour }}-subtle text-{{ $colour }}">
                                    {{ ucfirst($activity->event ?: $activity->description) }}
                                </span>
                            </td>
                            <td style="max-width: 420px;">
                                @php($attributes = $activity->properties['attributes'] ?? [])
                                @php($old = $activity->properties['old'] ?? [])

                                @if($attributes)
                                    <div class="small">
                                        @foreach($attributes as $field => $value)
                                            <div class="text-truncate">
                                                <span class="text-muted">{{ str_replace('_', ' ', $field) }}:</span>
                                                @if(array_key_exists($field, $old) && $old[$field] !== $value)
                                                    <s class="text-muted">{{ \Illuminate\Support\Str::limit((string) $old[$field], 24) }}</s>
                                                    →
                                                @endif
                                                <strong>{{ \Illuminate\Support\Str::limit((string) $value, 32) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="small text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <p class="mb-0">No activity recorded yet.</p>
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
