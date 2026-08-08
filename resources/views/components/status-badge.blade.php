{{-- Props: $status text, $type: pos|neg|warn|info|neutral|violet --}}
<span class="badge badge-{{ $type ?? 'neutral' }}">
    <span class="dot-indicator" style="background:currentColor;"></span>{{ $status }}
</span>
