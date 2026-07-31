@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'placeholder' => null,
    // Optional extra HTML attributes (id, min, max, step, data-*) passed as
    // an array. They cannot be spread inside the component tag: an echo or a
    // directive there stops Blade parsing the tag as a component at all.
    'extra' => [],
])

@php
    $id = $extra['id'] ?? $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $current = old($name, $value);

    $extraHtml = collect($extra)
        ->except('id')
        ->map(fn ($v, $k) => $k.'="'.e($v).'"')
        ->implode(' ');
@endphp

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}@if($required) <span class="required-mark">*</span>@endif
        </label>
    @endif

    <input type="{{ $type }}"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ $type === 'password' ? '' : $current }}"
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           @if($required) required @endif
           {!! $extraHtml !!}
           {{ $attributes->except('id')->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
