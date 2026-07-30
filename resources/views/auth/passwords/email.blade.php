@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <h1 class="h4 mb-1">Reset your password</h1>
    <p class="text-muted small mb-4">
        Enter the email address on your account and we will send you a reset link.
    </p>

    @if(session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
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

        <button type="submit" class="btn btn-primary w-100">Send reset link</button>
    </form>

    <p class="text-center small mt-4 mb-0">
        <a href="{{ route('login') }}">Back to sign in</a>
    </p>
@endsection
