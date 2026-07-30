@php
    // Supports dotted keys so a column can reach through a relation,
    // e.g. 'division.name'.
    $value = data_get($record, $key);
    $type = $column['type'] ?? 'text';
@endphp

@switch($type)
    @case('image')
        @php($url = data_get($record, $column['url'] ?? $key.'_url') ?: $value)
        @if($url)
            <img src="{{ $url }}" alt="" class="thumb-sm">
        @else
            <span class="thumb-sm d-inline-grid" style="place-items:center;">
                <i class="fa-regular fa-image text-muted"></i>
            </span>
        @endif
        @break

    @case('boolean')
        @if($value)
            <span class="badge bg-success-subtle text-success">{{ $column['true'] ?? 'Yes' }}</span>
        @else
            <span class="badge bg-secondary-subtle text-secondary">{{ $column['false'] ?? 'No' }}</span>
        @endif
        @break

    @case('badge')
        @php($colour = data_get($record, $column['color'] ?? '') ?: ($column['variant'] ?? 'secondary'))
        <span class="badge bg-{{ $colour }}-subtle text-{{ $colour }}">
            {{ data_get($record, $column['text'] ?? $key) ?: '—' }}
        </span>
        @break

    @case('date')
        {{ $value ? \Illuminate\Support\Carbon::parse($value)->format($column['format'] ?? 'j M Y') : '—' }}
        @break

    @case('datetime')
        @if($value)
            <span data-bs-toggle="tooltip" title="{{ \Illuminate\Support\Carbon::parse($value)->format('j M Y, H:i') }}">
                {{ \Illuminate\Support\Carbon::parse($value)->diffForHumans() }}
            </span>
        @else
            —
        @endif
        @break

    @case('number')
        {{ is_numeric($value) ? number_format((float) $value) : '—' }}
        @break

    @case('title')
        <div class="fw-semibold">{{ $value }}</div>
        @if(! empty($column['sub']) && data_get($record, $column['sub']))
            <div class="small text-muted">{{ \Illuminate\Support\Str::limit(data_get($record, $column['sub']), 70) }}</div>
        @endif
        @break

    @case('link')
        @if($value)
            <a href="{{ data_get($record, $column['href'] ?? 'url') }}" target="_blank" rel="noopener">
                {{ $value }} <i class="fa-solid fa-arrow-up-right-from-square ms-1 small"></i>
            </a>
        @else
            —
        @endif
        @break

    @default
        {{ filled($value) ? \Illuminate\Support\Str::limit((string) $value, $column['limit'] ?? 70) : '—' }}
@endswitch
