@props([
    'name',
    'label' => 'Image',
    'current' => null,
    'help' => 'JPG, PNG, WebP, GIF or SVG. Large images are resized automatically.',
    'accept' => 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml',
])

@php
    $id = 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
@endphp

<div class="mb-3">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>

    <div class="d-flex align-items-start gap-3">
        <img id="{{ $id }}-preview"
             src="{{ $current ?: '' }}"
             alt=""
             class="thumb-sm {{ $current ? '' : 'd-none' }}"
             style="width:72px;height:72px;">

        <div class="flex-grow-1">
            <input type="file"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   accept="{{ $accept }}"
                   data-image-preview="#{{ $id }}-preview"
                   {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>

            @error($name)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if($help)
                <div class="form-text">{{ $help }}</div>
            @endif

            @if($current)
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_{{ $name }}" value="1" id="{{ $id }}-remove">
                    <label class="form-check-label small text-danger" for="{{ $id }}-remove">
                        Remove the current image
                    </label>
                </div>
            @endif
        </div>
    </div>
</div>
