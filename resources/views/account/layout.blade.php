@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'My Account',
        'subtitle' => 'Your quotation requests, applications and enquiries in one place.',
        'breadcrumbs' => ['My Account' => null],
    ])

    <section class="section">
        <div class="container">

            @unless(auth()->user()->hasVerifiedEmail())
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div>
                        <strong>Please confirm your email address.</strong>
                        We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                        Until it is confirmed you can browse, but you cannot submit a quotation
                        request, an application or an enquiry.
                        <form method="POST" action="{{ route('verification.resend') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">Resend the link</button>
                        </form>
                    </div>
                </div>
            @endunless

            <div class="row g-4">
                <aside class="col-lg-3">
                    <div class="card-soft p-3 mb-4">
                        <div class="text-center p-3 border-bottom mb-2">
                            <img src="{{ auth()->user()->avatar_url }}" alt=""
                                 class="rounded-circle mb-2" width="72" height="72" style="object-fit: cover;">
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                            <div class="small text-muted">{{ auth()->user()->email }}</div>
                        </div>

                        <div class="nav flex-column nav-pills">
                            <a class="nav-link text-start {{ active_when('account.dashboard') }}"
                               href="{{ route('account.dashboard') }}">
                                <i class="fa-solid fa-gauge-high me-2"></i>Overview
                            </a>
                            <a class="nav-link text-start {{ active_when('account.quotes*') }}"
                               href="{{ route('account.quotes') }}">
                                <i class="fa-solid fa-file-invoice-dollar me-2"></i>My Requests
                            </a>
                            <a class="nav-link text-start {{ active_when('account.applications') }}"
                               href="{{ route('account.applications') }}">
                                <i class="fa-solid fa-briefcase me-2"></i>My Applications
                            </a>
                            <a class="nav-link text-start {{ active_when('account.messages') }}"
                               href="{{ route('account.messages') }}">
                                <i class="fa-solid fa-envelope me-2"></i>My Enquiries
                            </a>
                            <a class="nav-link text-start {{ active_when('account.profile') }}"
                               href="{{ route('account.profile') }}">
                                <i class="fa-solid fa-user me-2"></i>My Details
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
                        <h2 class="h6">Need a quotation?</h2>
                        <p class="small text-muted">
                            Tell us what you need and we will respond in writing.
                        </p>
                        <a href="{{ route('site.quote.create') }}" class="btn btn-primary btn-sm w-100">
                            Request a Quote
                        </a>
                    </div>
                </aside>

                <div class="col-lg-9">
                    @yield('account-content')
                </div>
            </div>
        </div>
    </section>

@endsection
