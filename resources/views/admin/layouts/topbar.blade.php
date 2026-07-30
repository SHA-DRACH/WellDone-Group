<div class="admin-topbar">
    <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" data-sidebar-toggle aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <a href="{{ route('site.home') }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
        <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>View website
    </a>

    <div class="ms-auto d-flex align-items-center gap-2">
        @can('quotes.view')
            <a href="{{ route('admin.quotes.index', ['status' => 'new']) }}"
               class="btn btn-light btn-sm position-relative" data-bs-toggle="tooltip" title="New quote requests">
                <i class="fa-solid fa-bell"></i>
                @php($newQuotes = \Illuminate\Support\Facades\Cache::get('admin.sidebar.badges')['quotes'] ?? 0)
                @if($newQuotes)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $newQuotes }}<span class="visually-hidden">new quote requests</span>
                    </span>
                @endif
            </a>
        @endcan

        <div class="dropdown">
            <button class="btn btn-light btn-sm d-flex align-items-center gap-2 dropdown-toggle"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ auth()->user()->avatar_url }}" alt="" width="26" height="26"
                     class="rounded-circle" style="object-fit: cover;">
                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <div class="fw-semibold small">{{ auth()->user()->name }}</div>
                    <div class="text-muted" style="font-size:.78rem;">{{ auth()->user()->email }}</div>
                    <div class="mt-1">
                        @foreach(auth()->user()->getRoleNames() as $role)
                            <span class="badge bg-primary-subtle text-primary">{{ $role }}</span>
                        @endforeach
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.profile.edit') }}">
                        <i class="fa-solid fa-user me-2"></i>My profile
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>
