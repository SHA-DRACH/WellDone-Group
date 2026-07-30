@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="h4 mb-1">Sign in</h1>
    <p class="text-muted small mb-4">Access the WellDone Group control panel.</p>

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

    <p class="text-muted small text-center mt-4 mb-0">
        Accounts are created by an administrator. If you need access, contact your ICT manager.
    </p>
@endsection
