@extends('layouts.auth')

@section('title', 'Enter your code')

@section('content')
    <h1 class="h4 mb-1">Enter your code</h1>
    <p class="text-muted small mb-4">
        Check your inbox for a six-digit code, then choose a new password.
    </p>

    @if(session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.otp.update') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email', $email) }}"
                   required autocomplete="email" maxlength="190">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="mb-3">
            <label for="code" class="form-label">Six-digit code</label>
            <input id="code" type="text"
                   class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                   name="code"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   pattern="[0-9]*"
                   maxlength="6"
                   placeholder="000000"
                   style="letter-spacing:.5em; font-weight:600;"
                   required autofocus>
            @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div class="form-text">Expires ten minutes after it was sent.</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="new-password">
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div class="form-text">At least 8 characters, with upper and lower case and a number.</div>
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label">Confirm new password</label>
            <input id="password-confirm" type="password" class="form-control"
                   name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-key me-2"></i>Set new password
        </button>
    </form>

    <p class="text-center small mt-4 mb-0">
        Did not get a code? <a href="{{ route('password.otp.request') }}">Request another</a>
    </p>
@endsection
