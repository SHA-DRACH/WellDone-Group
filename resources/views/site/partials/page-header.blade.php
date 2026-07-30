{{--
    Shared page banner.
    Expects: $title, optional $subtitle, and $breadcrumbs as [label => url|null].
--}}
<section class="page-header">
    @if(\App\Support\Brand::hasLogo() && setting('company.watermark_header', true))
        <img src="{{ brand('logo_light') }}" alt="" aria-hidden="true"
             class="page-header__watermark" loading="lazy" decoding="async">
    @endif

    <div class="container">
        @isset($breadcrumbs)
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('site.home') }}">Home</a></li>
                    @foreach($breadcrumbs as $label => $url)
                        @if($url && ! $loop->last)
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endisset

        <h1>{{ $title }}</h1>

        @isset($subtitle)
            <p>{{ $subtitle }}</p>
        @endisset
    </div>
</section>
