@extends('admin.layouts.app')

@section('title', 'No division assigned')
@section('page-title', 'No division assigned')

@section('content')
    <div class="card-soft p-5 text-center">
        <div class="mb-3" style="font-size: 2.25rem; color: var(--bs-warning, #D4AF37);">
            <i class="fa-solid fa-diagram-project"></i>
        </div>

        <h2 class="h5 mb-2">Your account is not attached to a division yet</h2>

        <p class="text-muted mb-4" style="max-width: 34rem; margin-inline: auto;">
            Your role is scoped to one business division, and either none has been
            recorded against your account or the one that was is no longer active.
            Until that is set you have nothing to manage — this is deliberate, so
            that an unfinished account never shows the whole group by accident.
        </p>

        <p class="small text-muted mb-0">
            Ask an administrator to set your division under
            <strong>Access&nbsp;control&nbsp;&rarr;&nbsp;Users</strong>.
        </p>
    </div>
@endsection
