@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <h1 class="h4 mb-1">Verify your email address</h1>

    @if(session('resent'))
        <div class="alert alert-success small" role="status">
            A fresh verification link has been sent to your email address.
        </div>
    @endif

    <p class="text-muted small mb-4">
        Before you can use the control panel, please follow the link we emailed to
        <strong>{{ auth()->user()->email }}</strong>. If it has not arrived, we can send another.
    </p>

    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit" class="btn btn-primary w-100">Resend verification email</button>
    </form>

    <div class="d-flex align-items-center gap-3 my-4">
        <hr class="flex-grow-1 m-0">
        <span class="text-muted small text-uppercase" style="letter-spacing:.08em; font-size:.72rem;">or</span>
        <hr class="flex-grow-1 m-0">
    </div>

    {{-- A code is easier on a phone, where the emailed link often opens in a
         browser with no session and the verification quietly fails. --}}
    <a href="{{ route('otp.verify') }}" class="btn btn-outline-primary w-100">
        <i class="fa-solid fa-hashtag me-2"></i>Verify with a code instead
    </a>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link w-100 text-muted small">Sign out</button>
    </form>
@endsection
