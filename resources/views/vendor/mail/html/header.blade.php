@props(['url'])
@php
    $brandWordmarkPath = public_path(config('brand.assets.wordmark'));
    $brandWordmarkSrc = isset($message) && is_file($brandWordmarkPath)
        ? $message->embed($brandWordmarkPath)
        : asset(config('brand.assets.wordmark'));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display:inline-block;">
<img src="{{ $brandWordmarkSrc }}" alt="AI Orbit" width="230" style="display:block;width:230px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">
</a>
</td>
</tr>
