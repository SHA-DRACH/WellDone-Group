<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') — {{ setting('company.name') }} CMS</title>

    <link rel="icon" href="{{ brand('favicon') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-body">

    @include('admin.layouts.sidebar')

    <div class="admin-backdrop"></div>

    <div class="admin-main">
        @include('admin.layouts.topbar')

        <div class="admin-content">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">@yield('page-title', View::getSection('title', 'Dashboard'))</h1>
                    @hasSection('page-subtitle')
                        <p class="text-muted mb-0">@yield('page-subtitle')</p>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @yield('page-actions')
                </div>
            </div>

            @include('admin.partials.flash')

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
