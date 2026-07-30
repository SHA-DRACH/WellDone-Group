@php($meta = seo()->resolve())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('site.partials.meta', ['meta' => $meta])

    <link rel="icon" href="{{ brand('favicon') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ brand('favicon') }}">
    <meta name="theme-color" content="#0b3c91">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @stack('head')

    @if($ga = setting('seo.google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($ga));
        </script>
    @endif
</head>
<body class="site-body @yield('body-class')">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    @if($notice = setting('system.maintenance_notice'))
        <div class="alert alert-warning rounded-0 border-0 text-center mb-0 py-2" role="status">
            <i class="fa-solid fa-circle-info me-1"></i>{{ $notice }}
        </div>
    @endif

    @include('site.partials.header')

    <main id="main-content">
        @include('site.partials.flash')

        @yield('content')
    </main>

    @include('site.partials.footer')

    <div class="floating-actions">
        @if($whatsapp = setting('contact.whatsapp'))
            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}"
               class="float-btn float-btn--whatsapp"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="Chat with us on WhatsApp"
               data-bs-toggle="tooltip"
               data-bs-placement="left"
               title="Chat on WhatsApp">
                <i class="fa-brands fa-whatsapp"></i>
            </a>
        @endif

        <button type="button" class="float-btn float-btn--top" data-back-to-top aria-label="Back to top">
            <i class="fa-solid fa-arrow-up"></i>
        </button>
    </div>

    @stack('scripts')
</body>
</html>
