@extends('layouts.auth')

@section('title', 'Confirm password')

@section('content')
    <h1 class="h4 mb-1">Confirm your password</h1>
    <p class="text-muted small mb-4">
        Please re-enter your password to continue to this area.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="current-password" autofocus>
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">Confirm</button>

        @if(Route::has('password.request'))
            <p class="text-center small mt-3 mb-0">
                <a href="{{ route('password.request') }}">Forgotten your password?</a>
            </p>
        @endif
    </form>
@endsection
