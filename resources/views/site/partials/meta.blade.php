{{-- Resolved by App\Services\SeoManager: controller override → model row → route row → site default. --}}
<title>{{ $meta['title'] }}</title>

<meta name="description" content="{{ $meta['description'] }}">
@if($meta['keywords'])
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ setting('company.name') }}">
<meta property="og:title" content="{{ $meta['og_title'] }}">
<meta property="og:description" content="{{ $meta['og_description'] }}">
<meta property="og:url" content="{{ $meta['canonical'] }}">
@if($meta['og_image'])
    <meta property="og:image" content="{{ $meta['og_image'] }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $meta['og_title'] }}">
<meta name="twitter:description" content="{{ $meta['og_description'] }}">
@if($meta['og_image'])
    <meta name="twitter:image" content="{{ $meta['og_image'] }}">
@endif

@if($verification = setting('seo.google_site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif

@if($meta['schema'])
    <script type="application/ld+json">{!! json_encode($meta['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
