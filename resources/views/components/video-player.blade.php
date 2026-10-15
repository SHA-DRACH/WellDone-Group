@props([
    'src' => null,       // an uploaded file's URL
    'embed' => null,     // VideoEmbed::from() result for a YouTube/Vimeo link
    'poster' => null,
    'title' => 'Video',
])

{{--
    One player for both kinds of video.

    An uploaded file plays in the browser's own player. A YouTube or Vimeo
    link is not loaded until the visitor presses play: until then it is just
    the poster and a button, so the page does not pull in a third-party
    player — and its cookies — for people who never watch.
--}}

@if($src)
    <div {{ $attributes->merge(['class' => 'video-frame']) }}>
        <video controls preload="metadata" playsinline
               @if($poster) poster="{{ $poster }}" @endif
               aria-label="{{ $title }}">
            <source src="{{ $src }}" type="video/mp4">
            Your browser cannot play this video.
            <a href="{{ $src }}">Download it instead</a>.
        </video>
    </div>
@elseif($embed)
    <div {{ $attributes->merge(['class' => 'video-frame video-frame--embed']) }}
         data-video-embed="{{ $embed['embed'] }}&autoplay=1"
         data-video-title="{{ $title }}"
         @if($poster) style="background-image: url('{{ $poster }}');" @endif>
        <button type="button" class="video-frame__play" aria-label="Play: {{ $title }}">
            <i class="fa-solid fa-play" aria-hidden="true"></i>
        </button>
        <span class="video-frame__provider">
            <i class="fa-brands fa-{{ $embed['provider'] }}" aria-hidden="true"></i>
            Plays from {{ ucfirst($embed['provider']) }}
        </span>
    </div>
@endif
