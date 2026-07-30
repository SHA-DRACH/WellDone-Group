@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $current = old($name, $value);
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
           {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
