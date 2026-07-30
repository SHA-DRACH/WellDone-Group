@props([
    'name',
    'label',
    'value' => false,
    'help' => null,
    'switch' => true,
])

@php
    $id = $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $checked = (bool) old($name, $value);
@endphp

<div class="mb-3">
    {{-- Hidden companion so an unchecked box still posts a value --}}
    <input type="hidden" name="{{ $name }}" value="0">

    <div class="form-check {{ $switch ? 'form-switch' : '' }}">
        <input class="form-check-input @error($name) is-invalid @enderror"
               type="checkbox"
               role="{{ $switch ? 'switch' : 'checkbox' }}"
               id="{{ $id }}"
               name="{{ $name }}"
               value="1"
               @checked($checked)
               {{ $attributes->except('class') }}>

        <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>

        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
