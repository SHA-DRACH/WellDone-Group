<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Sign in') — {{ setting('company.name') }}</title>

    <link rel="icon" href="{{ brand('favicon') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="auth-shell">
        <div class="w-100" style="max-width: 460px;">
            <div class="auth-brand">
                <a href="{{ route('site.home') }}" class="d-inline-flex flex-column align-items-center text-decoration-none">
                    @if(\App\Support\Brand::hasLogo())
                        <img src="{{ brand('logo_light') }}" alt="{{ setting('company.name') }}"
                             width="96" height="96"
                             style="object-fit: contain; background: #fff; border-radius: 50%; padding: 4px;">
                    @else
                        <span class="brand-mark mb-2" style="width:64px;height:64px;font-size:1.5rem;">
                            <strong>WD</strong>
                        </span>
                    @endif

                    <span class="h5 text-white mt-3 mb-1">{{ setting('company.name') }}</span>
                    <span class="small" style="color: #e5c766; letter-spacing:.12em; text-transform:uppercase; font-size:.72rem;">
                        {{ setting('company.slogan') }}
                    </span>
                </a>
            </div>

            <div class="auth-card">
                @yield('content')
            </div>

            <p class="text-center mt-4 mb-0 small" style="color: rgba(255,255,255,.6);">
                &copy; {{ now()->year }} {{ setting('company.legal_name', setting('company.name')) }}
                &middot; <a href="{{ route('site.home') }}" style="color: rgba(255,255,255,.75);">Back to website</a>
            </p>
        </div>
    </div>
</body>
</html>
