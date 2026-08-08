{{-- Props: $value (0-100), $segments (default 8) --}}
@php
    $segments = $segments ?? 8;
    $filled = (int) round(($value / 100) * $segments);
    $tone = $value >= 70 ? '' : ($value >= 40 ? 'warn' : 'neg');
@endphp
<div class="score-meter">
    <div class="score-meter__bars">
        @for($i = 0; $i < $segments; $i++)
            <i class="{{ $i < $filled ? 'on '.$tone : '' }}"></i>
        @endfor
    </div>
    <span class="score-meter__val">{{ $value }}%</span>
</div>
