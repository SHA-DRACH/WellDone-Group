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

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link w-100 text-muted small">Sign out</button>
    </form>
@endsection
