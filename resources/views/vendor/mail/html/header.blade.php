@props(['url'])
@php
    $brandWordmarkPath = public_path(config('brand.assets.wordmark'));
    $brandWordmarkSrc = isset($message) && is_file($brandWordmarkPath)
        ? $message->embed($brandWordmarkPath)
        : asset(config('brand.assets.wordmark'));
@endphp
<tr>
<td class="header">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $url }}" style="display:inline-block;text-decoration:none;">
                    <img src="{{ $brandWordmarkSrc }}" alt="AI Orbit" width="176" class="brand-wordmark">
                </a>
            </td>
            <td align="right" class="header-tagline">
                Explore. Compare. Stay Ahead.
            </td>
        </tr>
    </table>
</td>
</tr>
