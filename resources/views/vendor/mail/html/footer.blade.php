<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<p class="footer-company">{{ setting('company.legal_name', setting('company.name')) }}</p>
<p class="footer-detail">
{{ collect([setting('contact.address_line1'), setting('contact.city'), setting('contact.country')])->filter()->implode(', ') }}
</p>
<p class="footer-detail">
@if($phone = setting('contact.phone_primary')){{ $phone }}@endif
@if($email = setting('contact.email_general')) &middot; <a href="mailto:{{ $email }}">{{ $email }}</a>@endif
</p>
<p class="footer-detail">
<a href="{{ route('site.home') }}">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}</a>
</p>
<p class="footer-legal">
&copy; {{ now()->year }} {{ setting('company.legal_name', setting('company.name')) }}. All rights reserved.<br>
This message was sent to you because you contacted us or hold an account on our website.
</p>
</td>
</tr>
</table>
</td>
</tr>
