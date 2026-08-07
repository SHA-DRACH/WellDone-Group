@extends('layouts.auth')

@section('title', 'Choose your password')

@section('content')
    <h1 class="h4 mb-1">Choose your own password</h1>
    <p class="text-muted small mb-4">
        Your first password was set for you, which means somebody else knows it.
        Please replace it before you go any further.
    </p>

    <form method="POST" action="{{ route('password.change.update') }}" novalidate>
        @csrf

        <x-form.input name="current_password" type="password" label="The password you were given"
                      :required="true" autocomplete="current-password" />

        <x-form.input name="password" type="password" label="New password"
                      :required="true" autocomplete="new-password"
                      help="At least 8 characters, with upper and lower case letters and a number." />

        <x-form.input name="password_confirmation" type="password" label="Confirm new password"
                      :required="true" autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="fa-solid fa-key me-2"></i>Set my password
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link w-100 text-muted small">Sign out</button>
    </form>
@endsection
