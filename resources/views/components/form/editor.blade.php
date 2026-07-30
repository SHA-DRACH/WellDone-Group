@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'help' => 'Formatting is preserved. Pasted markup is sanitised before it is saved.',
])

@php
    $id = 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
@endphp

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $id }}-input">
            {{ $label }}@if($required) <span class="required-mark">*</span>@endif
        </label>
    @endif

    {{--
        Trix binds to the hidden input; the visible editor writes back into it
        on every change, so the value posts exactly like a normal field.
    --}}
    <input id="{{ $id }}-input" type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}">
    <trix-editor input="{{ $id }}-input"
                 class="trix-content form-control p-2 @error($name) is-invalid @enderror"
                 style="min-height: 260px;"></trix-editor>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
