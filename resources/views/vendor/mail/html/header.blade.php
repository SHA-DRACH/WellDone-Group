@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(\App\Support\Brand::hasLogo())
{{--
    The logo is referenced by absolute URL, so it has to be reachable from the
    recipient's mail client. On a local machine (127.0.0.1) it will not load —
    that is expected, and resolves as soon as the site is on a public domain.
    The company name below it is always visible either way.
--}}
<img src="{{ brand('logo') }}" class="logo" alt="{{ setting('company.name') }}">
<div class="brand-name">{{ setting('company.name') }}</div>
@if($slogan = setting('company.slogan'))
<div class="brand-slogan">{{ $slogan }}</div>
@endif
@else
<div class="brand-name">{{ setting('company.name', config('app.name')) }}</div>
@if($slogan = setting('company.slogan'))
<div class="brand-slogan">{{ $slogan }}</div>
@endif
@endif
</a>
</td>
</tr>
