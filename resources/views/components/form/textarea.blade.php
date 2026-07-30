@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'help' => null,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
@endphp

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}@if($required) <span class="required-mark">*</span>@endif
        </label>
    @endif

    <textarea id="{{ $id }}"
              name="{{ $name }}"
              rows="{{ $rows }}"
              @if($placeholder) placeholder="{{ $placeholder }}" @endif
              @if($required) required @endif
              {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
