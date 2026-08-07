@props(['url'])
@php
    /*
     * The logo is sent with the message rather than linked.
     *
     * A linked logo is a request the recipient's mail client has to make back
     * to this server: it fails outright while the site is on 127.0.0.1 (which,
     * on the recipient's machine, is their own computer), it fails behind any
     * private network, and most clients refuse remote images by default even
     * when the URL is reachable.
     *
     * Naming the content id here is what asks App\Listeners\EmbedBrandLogo to
     * attach the file as the message goes out — this component cannot do it
     * itself, because a Blade component's scope never sees the message being
     * assembled. Artwork hosted elsewhere has no local file to attach and
     * stays a plain URL.
     */
    $logoSrc = \App\Support\Brand::logoPath()
        ? 'cid:'.\App\Listeners\EmbedBrandLogo::CONTENT_ID
        : brand('logo');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(\App\Support\Brand::hasLogo())
<img src="{{ $logoSrc }}" class="logo" alt="{{ setting('company.name') }}">
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
