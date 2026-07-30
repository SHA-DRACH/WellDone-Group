@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')
    <h1 class="h4 mb-1">Choose a new password</h1>
    <p class="text-muted small mb-4">Pick something you have not used elsewhere.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <input id="password" type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="new-password">
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <div class="form-text">
                At least 8 characters, including upper and lower case, a number and a symbol.
            </div>
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label">Confirm new password</label>
            <input id="password-confirm" type="password" class="form-control"
                   name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100">Set new password</button>
    </form>
@endsection
