@php
    // Counts for the "needs attention" badges. Cheap COUNT queries, cached
    // briefly so they do not run on every single admin page load.
    $badges = \Illuminate\Support\Facades\Cache::remember('admin.sidebar.badges', now()->addMinutes(2), fn () => [
        'quotes' => \App\Models\QuoteRequest::query()->where('status', 'new')->count(),
    ]);

    $myDivision = auth()->user()->division;
    $allDivisions = \App\Support\Navigation::isDivisionScoped(auth()->user())
        ? collect()
        : \App\Models\Division::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']);
@endphp

<aside class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
        @if(\App\Support\Brand::hasLogo())
            <img src="{{ brand('logo_light') }}" alt="" width="58" height="40"
                 style="border-radius:.5rem; object-fit:contain; background:#fff; padding:3px;">
        @else
            <span class="brand-mark" aria-hidden="true"><strong>WD</strong></span>
        @endif
        <span>
            WellDone
            <small>Control Panel</small>
        </span>
    </a>

    <nav class="admin-nav" aria-label="Admin navigation">
        <a class="nav-link {{ active_when('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">
            <i class="fa-solid fa-gauge-high"></i> Group Dashboard
        </a>

        {{-- ------------------------------------------------ Division dashboards --}}
        <div class="nav-section">Division Dashboards</div>

        @if($myDivision)
            <a class="nav-link {{ request()->routeIs('admin.division.dashboard') && request()->route('division')?->id === $myDivision->id ? 'active' : '' }}"
               href="{{ route('admin.division.dashboard', $myDivision->slug) }}">
                <i class="{{ $myDivision->icon }}"></i> {{ $myDivision->name }}
                <span class="badge bg-gold text-dark rounded-pill">Mine</span>
            </a>
        @endif

        @foreach($allDivisions as $division)
            @continue($myDivision && $division->id === $myDivision->id)
            <a class="nav-link {{ request()->routeIs('admin.division.dashboard') && request()->route('division')?->id === $division->id ? 'active' : '' }}"
               href="{{ route('admin.division.dashboard', $division->slug) }}">
                <i class="{{ $division->icon }}"></i> {{ $division->name }}
            </a>
        @endforeach

        {{-- ------------------------------------------------ Leads --}}
        @can('quotes.view')
            <div class="nav-section">Leads &amp; Requests</div>

            <a class="nav-link {{ active_when('admin.quotes.*') }}" href="{{ route('admin.quotes.index') }}">
                <i class="fa-solid fa-file-invoice-dollar"></i> Quote Requests
                @if($badges['quotes']) <span class="badge bg-danger rounded-pill">{{ $badges['quotes'] }}</span> @endif
            </a>
        @endcan

        {{-- ------------------------------------------------ Structure --}}
        @canany(['divisions.view', 'services.view'])
            <div class="nav-section">Corporate Structure</div>

            @can('divisions.view')
                <a class="nav-link {{ active_when('admin.divisions.*') }}" href="{{ route('admin.divisions.index') }}">
                    <i class="fa-solid fa-sitemap"></i> Divisions
                </a>
            @endcan
            @can('services.view')
                <a class="nav-link {{ active_when('admin.services.*') }}" href="{{ route('admin.services.index') }}">
                    <i class="fa-solid fa-list-check"></i> Services
                </a>
            @endcan
        @endcanany

        {{-- ------------------------------------------------ Platform --}}
        @canany(['users.view', 'settings.view'])
            <div class="nav-section">Administration</div>

            @can('users.view')
                <a class="nav-link {{ active_when('admin.users.*') }}" href="{{ route('admin.users.index') }}">
                    <i class="fa-solid fa-user-gear"></i> Users
                </a>
            @endcan
            @can('settings.view')
                <a class="nav-link {{ active_when('admin.settings.*') }}" href="{{ route('admin.settings.edit') }}">
                    <i class="fa-solid fa-gear"></i> Website Settings
                </a>
            @endcan
        @endcanany

        <a class="nav-link {{ active_when('admin.profile.*') }}" href="{{ route('admin.profile.edit') }}">
            <i class="fa-solid fa-user"></i> My Profile
        </a>
    </nav>
</aside>
