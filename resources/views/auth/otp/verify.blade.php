@extends('layouts.auth')

@section('title', 'Confirm your email')

@section('content')
    <h1 class="h4 mb-1">Confirm your email</h1>
    <p class="text-muted small mb-4">
        We emailed <strong>{{ $email }}</strong> a six-digit code and a link.
        Enter the code below, or click the link in that email — either one
        confirms the address, and you can sign in once it is confirmed.
    </p>

    @if(session('status'))
        <div class="alert alert-success small d-flex align-items-start gap-2" role="status">
            <i class="fa-solid fa-envelope-circle-check mt-1"></i>
            <div>{{ session('status') }}</div>
        </div>
    @endif

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
            <i class="fa-solid fa-circle-check me-2"></i>Confirm and continue
        </button>
    </form>

    {{-- Sending is its own form so requesting a code never submits a
         half-typed one. --}}
    <form method="POST" action="{{ route('otp.send') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-outline-secondary w-100">
            <i class="fa-solid fa-paper-plane me-2"></i>Send me a new code
        </button>
    </form>

    <div class="text-center mt-4">
        <p class="text-muted small mb-2">
            Check the spam folder if it has not arrived — the code and the link
            are in the same message.
        </p>

        {{-- Signed in but unconfirmed (a staff login HR created) versus a
             visitor who has not signed in at all: different ways out. --}}
        @if($signedIn ?? false)
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted">Sign out</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-link btn-sm text-muted">Back to sign in</a>
        @endif
    </div>
@endsection
