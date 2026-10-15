@props([
    'name',
    'label' => 'Video',
    'current' => null,
    'poster' => null,
    'duration' => null,
    'min' => 30,
    'max' => 60,
    'help' => null,
])

{{--
    An MP4 upload with a length limit.

    The browser reads the clip's length the moment it is chosen and refuses
    one outside the limit before the upload starts — a 90 MB file should not
    take a minute to send only to be turned away. The server checks again
    (App\Rules\VideoLength), since the browser check is a courtesy, not a
    control.
--}}

@php
    $id = 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $limits = \App\Rules\VideoLength::human((int) $min).' to '.\App\Rules\VideoLength::human((int) $max);
@endphp

<div class="mb-3">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>

    @if($current)
        <div class="video-field__current mb-2">
            <video src="{{ $current }}" @if($poster) poster="{{ $poster }}" @endif
                   controls preload="metadata" playsinline class="w-100 rounded border bg-dark"
                   style="max-height: 220px;"></video>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="small text-muted">
                    <i class="fa-solid fa-film me-1"></i>Current video
                    @if($duration) · {{ \App\Rules\VideoLength::human((int) $duration) }} @endif
                </span>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remove_{{ $name }}" value="1" id="{{ $id }}-remove">
                    <label class="form-check-label small text-danger" for="{{ $id }}-remove">Remove</label>
                </div>
            </div>
        </div>
    @endif

    <input type="file"
           id="{{ $id }}"
           name="{{ $name }}"
           accept="video/mp4,video/x-m4v,video/quicktime,.mp4,.m4v,.mov"
           data-video-min="{{ $min }}"
           data-video-max="{{ $max }}"
           {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}>

    <div class="invalid-feedback" data-video-feedback>
        @error($name){{ $message }}@enderror
    </div>
    <div class="form-text" data-video-info>
        {{ $help ?? 'MP4, '.$limits.', up to '.round(\App\Rules\VideoLength::MAX_KB / 1024).' MB.' }}
    </div>
</div>
