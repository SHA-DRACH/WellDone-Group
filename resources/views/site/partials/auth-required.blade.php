{{--
    Shown above a form that only a verified account may submit.

    Expects $what — a short phrase describing the action, e.g.
    "send a quotation request".
--}}
@guest
    <div class="alert alert-primary d-flex flex-wrap align-items-center gap-3" role="status">
        <i class="fa-solid fa-circle-user fa-lg"></i>
        <div class="flex-grow-1">
            <strong>Please sign in to {{ $what }}.</strong>
            <div class="small">
                An account lets you track everything you send us, and means we can
                reach you about it. It takes less than a minute to create.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Create an account</a>
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Sign in</a>
        </div>
    </div>
@else
    @unless(auth()->user()->hasVerifiedEmail())
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-3" role="status">
            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
            <div class="flex-grow-1">
                <strong>Please confirm your email address first.</strong>
                <div class="small">
                    We sent a link to {{ auth()->user()->email }}. You need to follow it
                    before you can {{ $what }}.
                </div>
            </div>
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">Resend the link</button>
            </form>
        </div>
    @endunless
@endguest
