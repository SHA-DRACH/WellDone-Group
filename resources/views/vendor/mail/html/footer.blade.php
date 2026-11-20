@php
    $company = setting('company.legal_name', setting('company.name'));
    $website = setting('contact.website') ?: rtrim(config('app.url'), '/');
    $divisions = \App\Models\Division::query()->active()->ordered()->pluck('name')
        ->map(fn ($n) => trim(preg_replace(['/^WellDone\s+/i', '/\s+Services$/i'], '', $n)));
    $socials = collect(['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'twitter' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube'])
        ->filter(fn ($label, $key) => filled(setting("social.$key")))
        ->map(fn ($label, $key) => ['label' => $label, 'url' => setting("social.$key")]);
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
@if($divisions->isNotEmpty())
<p class="footer-divisions">{{ $divisions->implode('  ·  ') }}</p>
@endif
<p class="footer-company">{{ $company }}</p>
<p class="footer-detail">
{{ collect([setting('contact.address_line1'), setting('contact.address_line2'), setting('contact.city'), setting('contact.country')])->filter()->implode(', ') }}
</p>
<p class="footer-detail">
@if($phone = setting('contact.phone_primary')){{ $phone }}@endif
@if($email = setting('contact.email_general')) &nbsp;·&nbsp; <a href="mailto:{{ $email }}">{{ $email }}</a>@endif
</p>
<p class="footer-detail">
<a href="{{ $website }}">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($website, '/')) }}</a>
@foreach($socials as $social)
&nbsp;·&nbsp; <a href="{{ $social['url'] }}">{{ $social['label'] }}</a>
@endforeach
</p>
<p class="footer-legal">
&copy; {{ now()->year }} {{ $company }}. All rights reserved.<br>
You are receiving this email because you have an account with us, subscribed to our updates, or contacted us through our website.
</p>
</td>
</tr>
</table>
</td>
</tr>
