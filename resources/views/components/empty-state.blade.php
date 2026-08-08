{{-- Props: $icon, $title, $desc --}}
<div class="state-block">
    <div class="state-block__icon"><i data-lucide="{{ $icon ?? 'inbox' }}"></i></div>
    <h4>{{ $title }}</h4>
    <p>{{ $desc }}</p>
    {{ $slot ?? '' }}
</div>
