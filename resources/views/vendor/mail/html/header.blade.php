@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(\App\Support\Brand::hasLogo())
<img src="{{ brand('logo') }}" class="logo" alt="{{ setting('company.name') }}" style="max-height: 72px; width: auto;">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
