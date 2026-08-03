@extends('layouts.auth')

@section('title', 'Create an account')

@section('content')
    <h1 class="h4 mb-1">Create your account</h1>
    <p class="text-muted small mb-4">
        An account lets you request a quotation, apply for vacancies, and follow
        everything you have sent us in one place.
    </p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- Honeypot: hidden from people, tempting to bots --}}
        <div class="d-none" aria-hidden="true">
            <label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Full name <span class="required-mark">*</span></label>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" value="{{ old('name') }}" required autocomplete="name" autofocus maxlength="150">
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email address <span class="required-mark">*</span></label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="190">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div class="form-text">We send a confirmation link here before your account can be used.</div>
        </div>

        <div class="row">
            <div class="col-sm-6 mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input id="phone" type="tel" class="form-control @error('phone') is-invalid @enderror"
                       name="phone" value="{{ old('phone') }}" autocomplete="tel" maxlength="40">
                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-sm-6 mb-3">
                <label for="company" class="form-label">Organisation</label>
                <input id="company" type="text" class="form-control @error('company') is-invalid @enderror"
                       name="company" value="{{ old('company') }}" autocomplete="organization" maxlength="150">
                @error('company')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password <span class="required-mark">*</span></label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="new-password">
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div class="form-text">At least 8 characters, with upper and lower case and a number.</div>
        </div>

        <div class="mb-3">
            <label for="password-confirm" class="form-label">Confirm password <span class="required-mark">*</span></label>
            <input id="password-confirm" type="password" class="form-control"
                   name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox"
                   name="terms" id="terms" value="1" required @checked(old('terms'))>
            <label class="form-check-label small" for="terms">
                I agree to the <a href="{{ route('site.terms') }}" target="_blank" rel="noopener">terms of use</a>
                and <a href="{{ route('site.privacy') }}" target="_blank" rel="noopener">privacy policy</a>.
                <span class="required-mark">*</span>
            </label>
            @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-user-plus me-2"></i>Create account
        </button>
    </form>

    @include('auth.partials.google-button', ['label' => 'Sign up with Google'])

    <p class="text-center small mt-4 mb-0">
        Already registered? <a href="{{ route('login') }}">Sign in</a>
    </p>

    <hr class="my-4">

    <p class="text-muted text-center mb-0" style="font-size:.78rem;">
        This form is for clients and job applicants. Staff accounts are created
        by an administrator.
    </p>
@endsection
