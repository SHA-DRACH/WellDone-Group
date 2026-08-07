@php
    $me = auth()->user();
    $scopedToDivision = \App\Support\Navigation::isDivisionScoped($me);

    /*
     * Counts for the "needs attention" badges. Cheap COUNT queries, cached
     * briefly so they do not run on every admin page load.
     *
     * Cached per division, not globally: a badge reading "12 new requests"
     * when only three are yours still discloses how busy the other divisions
     * are, and one shared cache key would serve whichever count happened to
     * be computed first to everybody.
     */
    $badgeKey = 'admin.sidebar.badges.'.($scopedToDivision ? 'division.'.$me->division_id : 'group');

    $badges = \Illuminate\Support\Facades\Cache::remember($badgeKey, now()->addMinutes(2), fn () => [
        'quotes' => \App\Models\QuoteRequest::query()->withinDivisionOf($me)->where('status', 'new')->count(),
        'messages' => \App\Models\ContactMessage::query()->withinDivisionOf($me)->where('status', 'new')->count(),
        'applications' => \App\Models\JobApplication::query()->withinDivisionOf($me)->where('status', 'new')->count(),
        'comments' => \App\Models\BlogComment::query()->where('is_approved', false)->count(),
    ]);

    $myDivision = $me->division;
    $allDivisions = $scopedToDivision
        ? collect()
        : \App\Models\Division::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']);
    /*
     * Two routes use a `division` parameter: the dashboard binds it to a
     * Division model, while the divisions resource passes the raw slug. Only
     * the bound model tells us which dashboard is open.
     */
    $routeDivision = request()->route('division');
    $currentDivisionId = $routeDivision instanceof \App\Models\Division ? $routeDivision->id : null;
@endphp

<aside class="admin-sidebar">
    <a href="{{ \App\Support\Navigation::homeRouteFor($me) }}" class="admin-brand">
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
        {{-- The group dashboard counts the whole company, so it is not a
             division head's to open. The controller redirects them anyway;
             this stops the link appearing at all. --}}
        @unless($scopedToDivision)
            <a class="nav-link {{ active_when('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">
                <i class="fa-solid fa-gauge-high"></i> Group Dashboard
            </a>
        @endunless

        {{-- ------------------------------------------------ Division dashboards --}}
        <div class="nav-section">{{ $scopedToDivision ? 'My Division' : 'Division Dashboards' }}</div>

        @if($myDivision)
            <a class="nav-link {{ $currentDivisionId === $myDivision->id ? 'active' : '' }}"
               href="{{ route('admin.division.dashboard', $myDivision->slug) }}">
                <i class="{{ $myDivision->icon }}"></i> {{ $myDivision->name }}
                <span class="badge bg-warning text-dark rounded-pill">Mine</span>
            </a>
        @endif

        @foreach($allDivisions as $division)
            @continue($myDivision && $division->id === $myDivision->id)
            <a class="nav-link {{ $currentDivisionId === $division->id ? 'active' : '' }}"
               href="{{ route('admin.division.dashboard', $division->slug) }}">
                <i class="{{ $division->icon }}"></i> {{ $division->name }}
            </a>
        @endforeach

        {{-- ------------------------------------------------ Leads --}}
        @canany(['quotes.view', 'contact.view', 'applications.view', 'newsletters.view'])
            <div class="nav-section">Leads &amp; Enquiries</div>

            @can('quotes.view')
                <a class="nav-link {{ active_when('admin.quotes.*') }}" href="{{ route('admin.quotes.index') }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Quote Requests
                    @if($badges['quotes']) <span class="badge bg-danger rounded-pill">{{ $badges['quotes'] }}</span> @endif
                </a>
            @endcan

            @can('contact.view')
                <a class="nav-link {{ active_when('admin.messages.*') }}" href="{{ route('admin.messages.index') }}">
                    <i class="fa-solid fa-envelope"></i> Messages
                    @if($badges['messages']) <span class="badge bg-danger rounded-pill">{{ $badges['messages'] }}</span> @endif
                </a>
            @endcan

            @can('applications.view')
                <a class="nav-link {{ active_when('admin.applications.*') }}" href="{{ route('admin.applications.index') }}">
                    <i class="fa-solid fa-user-tie"></i> Applications
                    @if($badges['applications']) <span class="badge bg-danger rounded-pill">{{ $badges['applications'] }}</span> @endif
                </a>
            @endcan

            @can('newsletters.view')
                <a class="nav-link {{ active_when('admin.newsletters.*') }}" href="{{ route('admin.newsletters.index') }}">
                    <i class="fa-solid fa-paper-plane"></i> Subscribers
                </a>
            @endcan
        @endcanany

        {{-- ------------------------------------------------ Structure --}}
        @canany(['divisions.view', 'services.view', 'clients.view'])
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
            @can('clients.view')
                <a class="nav-link {{ active_when('admin.clients.*') }}" href="{{ route('admin.clients.index') }}">
                    <i class="fa-solid fa-handshake"></i> Clients
                </a>
            @endcan
        @endcanany

        {{-- ------------------------------------------------ Content --}}
        @canany(['sliders.view', 'projects.view', 'galleries.view', 'blogs.view', 'testimonials.view', 'partners.view', 'downloads.view'])
            <div class="nav-section">Website Content</div>

            @can('sliders.view')
                <a class="nav-link {{ active_when('admin.sliders.*') }}" href="{{ route('admin.sliders.index') }}">
                    <i class="fa-solid fa-images"></i> Hero Slides
                </a>
            @endcan
            @can('projects.view')
                <a class="nav-link {{ active_when('admin.projects.*') }}" href="{{ route('admin.projects.index') }}">
                    <i class="fa-solid fa-diagram-project"></i> Projects
                </a>
            @endcan
            @can('galleries.view')
                <a class="nav-link {{ active_when('admin.galleries.*') }}" href="{{ route('admin.galleries.index') }}">
                    <i class="fa-regular fa-images"></i> Galleries
                </a>
            @endcan
            @can('blogs.view')
                <a class="nav-link {{ active_when('admin.blogs.*') }}" href="{{ route('admin.blogs.index') }}">
                    <i class="fa-regular fa-newspaper"></i> News &amp; Blog
                </a>
                <a class="nav-link {{ active_when('admin.blog-categories.*') }}" href="{{ route('admin.blog-categories.index') }}">
                    <i class="fa-solid fa-tags"></i> Blog Categories
                </a>
                @can('blogs.moderate')
                    <a class="nav-link {{ active_when('admin.comments.*') }}" href="{{ route('admin.comments.index') }}">
                        <i class="fa-regular fa-comments"></i> Comments
                        @if($badges['comments'])
                            <span class="badge bg-warning text-dark rounded-pill">{{ $badges['comments'] }}</span>
                        @endif
                    </a>
                @endcan
            @endcan
            @can('testimonials.view')
                <a class="nav-link {{ active_when('admin.testimonials.*') }}" href="{{ route('admin.testimonials.index') }}">
                    <i class="fa-solid fa-quote-left"></i> Testimonials
                </a>
            @endcan
            @can('partners.view')
                <a class="nav-link {{ active_when('admin.partners.*') }}" href="{{ route('admin.partners.index') }}">
                    <i class="fa-solid fa-award"></i> Partners
                </a>
            @endcan
            @can('downloads.view')
                <a class="nav-link {{ active_when('admin.downloads.*') }}" href="{{ route('admin.downloads.index') }}">
                    <i class="fa-solid fa-file-arrow-down"></i> Downloads
                </a>
            @endcan
        @endcanany

        {{-- ------------------------------------------------ Company profile --}}
        @can('company.view')
            <div class="nav-section">Company Profile</div>

            <a class="nav-link {{ active_when('admin.team-members.*') }}" href="{{ route('admin.team-members.index') }}">
                <i class="fa-solid fa-users"></i> Leadership &amp; Team
            </a>
            <a class="nav-link {{ active_when('admin.milestones.*') }}" href="{{ route('admin.milestones.index') }}">
                <i class="fa-solid fa-flag"></i> Milestones
            </a>
            <a class="nav-link {{ active_when('admin.certificates.*') }}" href="{{ route('admin.certificates.index') }}">
                <i class="fa-solid fa-certificate"></i> Certificates
            </a>
            <a class="nav-link {{ active_when('admin.csr-initiatives.*') }}" href="{{ route('admin.csr-initiatives.index') }}">
                <i class="fa-solid fa-hand-holding-heart"></i> CSR Initiatives
            </a>
            <a class="nav-link {{ active_when('admin.faqs.*') }}" href="{{ route('admin.faqs.index') }}">
                <i class="fa-regular fa-circle-question"></i> FAQs
            </a>
        @endcan

        {{-- ------------------------------------------------ Human Resources --}}
        @canany(['employees.view', 'departments.view', 'positions.view', 'announcements.view', 'careers.view'])
            <div class="nav-section">Human Resources</div>

            @can('employees.view')
                <a class="nav-link {{ active_when('admin.hr.*') }}" href="{{ route('admin.hr.dashboard') }}">
                    <i class="fa-solid fa-chart-pie"></i> HR Dashboard
                </a>
                <a class="nav-link {{ active_when('admin.employees.*') }}" href="{{ route('admin.employees.index') }}">
                    <i class="fa-solid fa-users"></i> Employees
                </a>
            @endcan

            @can('departments.view')
                <a class="nav-link {{ active_when('admin.departments.*') }}" href="{{ route('admin.departments.index') }}">
                    <i class="fa-solid fa-building-user"></i> Departments
                </a>
            @endcan
            @can('positions.view')
                <a class="nav-link {{ active_when('admin.positions.*') }}" href="{{ route('admin.positions.index') }}">
                    <i class="fa-solid fa-user-tag"></i> Positions
                </a>
            @endcan
            @can('announcements.view')
                <a class="nav-link {{ active_when('admin.announcements.*') }}" href="{{ route('admin.announcements.index') }}">
                    <i class="fa-solid fa-bullhorn"></i> Announcements
                </a>
            @endcan
            @can('careers.view')
                <a class="nav-link {{ active_when('admin.careers.*') }}" href="{{ route('admin.careers.index') }}">
                    <i class="fa-solid fa-briefcase"></i> Vacancies
                </a>
            @endcan
        @endcanany

        {{-- ------------------------------------------------ Platform --}}
        @canany(['users.view', 'roles.view', 'media.view', 'seo.view', 'settings.view', 'activity.view'])
            <div class="nav-section">Administration</div>

            @can('users.view')
                <a class="nav-link {{ active_when('admin.users.*') }}" href="{{ route('admin.users.index') }}">
                    <i class="fa-solid fa-user-gear"></i> Users
                </a>
            @endcan
            @can('roles.view')
                <a class="nav-link {{ active_when('admin.roles.*') }}" href="{{ route('admin.roles.index') }}">
                    <i class="fa-solid fa-shield-halved"></i> Roles &amp; Permissions
                </a>
            @endcan
            @can('media.view')
                <a class="nav-link {{ active_when('admin.media.*') }}" href="{{ route('admin.media.index') }}">
                    <i class="fa-regular fa-folder-open"></i> Media Library
                </a>
            @endcan
            @can('seo.view')
                <a class="nav-link {{ active_when('admin.seo.*') }}" href="{{ route('admin.seo.index') }}">
                    <i class="fa-solid fa-magnifying-glass-chart"></i> SEO Metadata
                </a>
            @endcan
            @can('settings.view')
                <a class="nav-link {{ active_when('admin.settings.*') }}" href="{{ route('admin.settings.edit') }}">
                    <i class="fa-solid fa-gear"></i> Website Settings
                </a>
            @endcan
            @can('activity.view')
                <a class="nav-link {{ active_when('admin.activity.*') }}" href="{{ route('admin.activity.index') }}">
                    <i class="fa-solid fa-clock-rotate-left"></i> Activity Log
                </a>
            @endcan
        @endcanany

        <div class="nav-section">Account</div>
        <a class="nav-link {{ active_when('admin.profile.*') }}" href="{{ route('admin.profile.edit') }}">
            <i class="fa-solid fa-user"></i> My Profile
        </a>
    </nav>
</aside>
