@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="h4 mb-1">Sign in</h1>
    <p class="text-muted small mb-4">
        Clients, applicants and staff all sign in here — you are taken to the
        right place automatically.
    </p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <span class="invalid-feedback" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label mb-0">Password</label>
                @if(Route::has('password.request'))
                    <a class="small" href="{{ route('password.request') }}">Forgotten?</a>
                @endif
            </div>
            <input id="password" type="password"
                   class="form-control mt-1 @error('password') is-invalid @enderror"
                   name="password" required autocomplete="current-password">
            @error('password')
                <span class="invalid-feedback" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
            <label class="form-check-label small" for="remember">Keep me signed in on this device</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign in
        </button>
    </form>

    @if(Route::has('register'))
        <div class="text-center mt-4">
            <p class="text-muted small mb-2">
                New here? Create a free account to request a quotation, apply for
                vacancies and track everything you send us.
            </p>
            <a href="{{ route('register') }}" class="btn btn-outline-primary w-100">
                <i class="fa-solid fa-user-plus me-2"></i>Create an account
            </a>
        </div>
    @endif

    <hr class="my-4">

    <p class="text-muted text-center mb-0" style="font-size:.78rem;">
        Staff accounts are created by an administrator — contact your ICT manager
        if you need access to the control panel.
    </p>
@endsection
