{{-- Section navigation across the HR module. Only shows what the user may open. --}}
@php
    $hrTabs = array_filter([
        ['route' => 'admin.hr.dashboard', 'match' => 'admin.hr.dashboard', 'icon' => 'fa-chart-pie', 'label' => 'Overview', 'can' => 'employees.view'],
        ['route' => 'admin.employees.index', 'match' => 'admin.employees.*', 'icon' => 'fa-users', 'label' => 'Employees', 'can' => 'employees.view'],
        ['route' => 'admin.training.index', 'match' => 'admin.training.*', 'icon' => 'fa-graduation-cap', 'label' => 'Training', 'can' => 'training.view'],
        ['route' => 'admin.employee-documents.index', 'match' => 'admin.employee-documents.*', 'icon' => 'fa-folder-open', 'label' => 'Documents', 'can' => 'employee_documents.view'],
        ['route' => 'admin.departments.index', 'match' => 'admin.departments.*', 'icon' => 'fa-building-user', 'label' => 'Departments', 'can' => 'departments.view'],
        ['route' => 'admin.positions.index', 'match' => 'admin.positions.*', 'icon' => 'fa-user-tag', 'label' => 'Positions', 'can' => 'positions.view'],
    ], fn ($tab) => auth()->user()->can($tab['can']));
@endphp

<nav class="module-tabs print-hide" aria-label="Human resources">
    @foreach($hrTabs as $tab)
        <a href="{{ route($tab['route']) }}" @class(['active' => request()->routeIs($tab['match'])])>
            <i class="fa-solid {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
        </a>
    @endforeach
</nav>
