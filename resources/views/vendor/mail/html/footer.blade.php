@props(['url'])
@php
    $brandWordmarkPath = public_path(config('brand.assets.wordmark'));
    $brandWordmarkSrc = isset($message) && is_file($brandWordmarkPath)
        ? $message->embed($brandWordmarkPath)
        : asset(config('brand.assets.wordmark'));
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
    <a href="{{ config('brand.url', config('app.url')) }}" class="footer-brand">
        <img src="{{ $brandWordmarkSrc }}" alt="AI Orbit" width="154" class="footer-wordmark">
    </a>
    <p class="footer-tagline">{{ config('brand.tagline', 'Explore • Compare • Stay Ahead') }}</p>
    <p class="footer-copy">© {{ date('Y') }} AI Orbit. All rights reserved.</p>
    <p class="footer-copy">This is an automated account email. Please do not reply directly to this message.</p>
</td>
</tr>
</table>
</td>
</tr>
