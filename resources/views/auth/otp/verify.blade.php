@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <h1 class="h4 mb-1">Verify your email</h1>
    <p class="text-muted small mb-4">
        We will send a six-digit code to <strong>{{ $email }}</strong>.
        Enter it below to confirm the address is yours.
    </p>

    @if(session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    {{-- Sending is its own form so requesting a code never submits a
         half-typed one. --}}
    <form method="POST" action="{{ route('otp.send') }}" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-outline-primary w-100">
            <i class="fa-solid fa-paper-plane me-2"></i>Send me a code
        </button>
    </form>

    <form method="POST" action="{{ route('otp.confirm') }}" novalidate>
        @csrf

        <div class="mb-4">
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
            <div class="form-text">The code expires ten minutes after it is sent.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-circle-check me-2"></i>Verify
        </button>
    </form>

    <div class="text-center mt-4">
        <p class="text-muted small mb-2">
            Prefer a link? The verification email we sent when your account was
            created still works.
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-muted">Sign out</button>
        </form>
    </div>
@endsection
