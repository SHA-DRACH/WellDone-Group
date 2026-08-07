@extends('layouts.auth')

@section('title', 'Your session expired')

@section('content')
    <div class="text-center">
        <div class="mb-3" style="font-size: 2.5rem; color: var(--bs-warning, #D4AF37);">
            <i class="fa-solid fa-clock-rotate-left"></i>
        </div>

        <h1 class="h4 mb-2">Your session expired</h1>

        <p class="text-muted small mb-4">
            This page sat open longer than the sign-in is kept alive, so the form could no
            longer prove it came from you. Nothing was saved and nothing was lost —
            sign in again and repeat the action.
        </p>

        <div class="d-grid gap-2">
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign in again
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                Back to the previous page
            </a>
        </div>
    </div>
@endsection
