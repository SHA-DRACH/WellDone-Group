@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'multiple' => false,
])

@php
    $id = $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $current = old($multiple ? rtrim($name, '[]') : $name, $value);
    $selected = $multiple ? (array) $current : $current;
@endphp

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}@if($required) <span class="required-mark">*</span>@endif
        </label>
    @endif

    <select id="{{ $id }}"
            name="{{ $name }}"
            @if($multiple) multiple @endif
            @if($required) required @endif
            {{ $attributes->merge(['class' => 'form-select'.($errors->has(rtrim($name, '[]')) ? ' is-invalid' : '')]) }}>

        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}"
                @selected($multiple ? in_array((string) $optionValue, array_map('strval', $selected), true) : (string) $selected === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error(rtrim($name, '[]'))
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
