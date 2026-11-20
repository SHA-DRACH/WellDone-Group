<x-mail::message>
{{--
    WellDone Group — the body of every notification email.

    The frame (header, card, footer) lives in resources/views/vendor/mail;
    this file arranges what goes inside the card: greeting, message, the one
    call to action, and a sign-off. Notifications keep building their content
    with MailMessage as before — nothing here needs touching to add a new one.
--}}
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@elseif ($level === 'error')
# Something needs your attention
@else
# Hello,
@endif

{{-- Intro lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="match ($level) { 'success', 'error' => $level, default => 'primary' }">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Sign-off: a closing phrase ("Kind regards,") is set apart; the lines
     after it are who the message is from. A sign-off that is only names
     ("Jane Doe / WellDone Group Inc.") is shown as names throughout. --}}
@php
    $signOff = collect(preg_split('/\r?\n/', (string) ($salutation ?: "Kind regards,\n".setting('company.name', config('app.name')))))
        ->map(fn ($l) => trim($l))
        ->filter()
        ->values();
    $lead = str_ends_with((string) $signOff->first(), ',') ? $signOff->shift() : null;
@endphp
<table class="signoff" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
@if ($lead)
<p class="signoff-lead">{{ $lead }}</p>
@endif
@foreach ($signOff as $line)
<p class="signoff-name">{{ $line }}</p>
@endforeach
@if ($slogan = setting('company.slogan'))
<p class="signoff-slogan">{{ $slogan }}</p>
@endif
</td>
</tr>
</table>

{{-- Subcopy: the plain link, for clients that block buttons --}}
@isset($actionText)
<x-slot:subcopy>
Button not working? Copy this link into your browser:<br>
<a href="{{ $actionUrl }}" class="subcopy-link">{{ $displayableActionUrl }}</a>
</x-slot:subcopy>
@endisset
</x-mail::message>
