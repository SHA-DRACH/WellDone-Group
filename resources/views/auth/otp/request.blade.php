@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')
    <h1 class="h4 mb-1">Reset your password</h1>
    <p class="text-muted small mb-4">
        Enter the address on your account and we will email you a six-digit code.
    </p>

    @if(session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.otp.send') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}"
                   required autocomplete="email" autofocus maxlength="190">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-paper-plane me-2"></i>Send me a code
        </button>
    </form>

    <div class="d-flex align-items-center gap-3 my-4">
        <hr class="flex-grow-1 m-0">
        <span class="text-muted small text-uppercase" style="letter-spacing:.08em; font-size:.72rem;">or</span>
        <hr class="flex-grow-1 m-0">
    </div>

    <a href="{{ route('password.request') }}" class="btn btn-outline-secondary w-100">
        Send me a reset link instead
    </a>

    <p class="text-center small mt-4 mb-0">
        <a href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection
