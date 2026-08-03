@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Staff Portal',
        'subtitle' => $employee->full_name.' · '.($employee->position?->title ?? 'Employee'),
        'breadcrumbs' => ['Staff Portal' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                <aside class="col-lg-3">
                    <div class="card-soft p-3 mb-4">
                        <div class="text-center p-3 border-bottom mb-2">
                            <img src="{{ $employee->photo_url }}" alt=""
                                 class="rounded-circle mb-2" width="82" height="82" style="object-fit: cover;">
                            <div class="fw-semibold">{{ $employee->full_name }}</div>
                            <div class="small text-muted">{{ $employee->employee_number }}</div>
                            <span class="badge bg-{{ $employee->status_color }}-subtle text-{{ $employee->status_color }} mt-2">
                                {{ $employee->status_label }}
                            </span>
                        </div>

                        <div class="nav flex-column nav-pills">
                            <a class="nav-link text-start {{ active_when('portal.dashboard') }}"
                               href="{{ route('portal.dashboard') }}">
                                <i class="fa-solid fa-gauge-high me-2"></i>Overview
                            </a>
                            <a class="nav-link text-start {{ active_when('portal.announcements*') }}"
                               href="{{ route('portal.announcements') }}">
                                <i class="fa-solid fa-bullhorn me-2"></i>Announcements
                            </a>
                            <a class="nav-link text-start {{ active_when('portal.profile') }}"
                               href="{{ route('portal.profile') }}">
                                <i class="fa-solid fa-id-card me-2"></i>My Details
                            </a>
                        </div>

                        <hr>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Sign out
                            </button>
                        </form>
                    </div>

                    <div class="card-soft p-4">
                        <h2 class="h6 mb-2">Need help?</h2>
                        <p class="small text-muted mb-3">
                            Human Resources can help with anything on your record that you
                            cannot change here.
                        </p>
                        @if($hr = setting('contact.email_hr'))
                            <a href="mailto:{{ $hr }}" class="btn btn-outline-primary btn-sm w-100">Contact HR</a>
                        @endif
                    </div>
                </aside>

                <div class="col-lg-9">
                    @yield('portal-content')
                </div>
            </div>
        </div>
    </section>

@endsection
