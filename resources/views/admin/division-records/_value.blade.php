{{-- One cell of a division log: formats a value by its field type. --}}
@php($value = $record->field($name))
@if($value === null || $value === '')
    <span class="text-muted">—</span>
@elseif($field['type'] === 'money')
    @money($value)
@elseif(in_array($field['type'], ['integer', 'number'], true))
    {{ number_format((float) $value, $field['type'] === 'integer' ? 0 : 2) }}@isset($field['unit'])<span class="text-muted small"> {{ $field['unit'] }}</span>@endisset
@elseif($field['type'] === 'date')
    {{ \Illuminate\Support\Carbon::parse($value)->format('j M Y') }}
@elseif($field['type'] === 'select')
    <span class="badge bg-light text-dark border">{{ $value }}</span>
@else
    {{ \Illuminate\Support\Str::limit($value, 48) }}
@endif
