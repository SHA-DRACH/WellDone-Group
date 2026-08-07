@extends('layouts.auth')

@section('title', 'Sign out')

@section('content')
    <div class="text-center">
        <h1 class="h4 mb-2">Sign out?</h1>

        <p class="text-muted small mb-4">
            You are signed in as <strong>{{ auth()->user()->email }}</strong>.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="d-grid gap-2">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-right-from-bracket me-2"></i>Sign out
            </button>
        </form>

        <a href="{{ \App\Support\Navigation::homeRouteFor(auth()->user()) }}"
           class="btn btn-outline-secondary btn-sm w-100 mt-2">
            Stay signed in
        </a>
    </div>
@endsection
