{{-- Props: $icon, $label, $value, $delta (e.g. '+12.4%'), $trend ('up'|'down') --}}
<div class="card kpi-card">
    <div class="kpi-top">
        <div class="kpi-icon"><i data-lucide="{{ $icon }}"></i></div>
    </div>
    <div class="kpi-value">{{ $value }}</div>
    <div class="kpi-label">{{ $label }}</div>
    @isset($delta)
        <span class="kpi-delta {{ $trend ?? 'up' }}">
            <i data-lucide="{{ ($trend ?? 'up') === 'up' ? 'arrow-up-right' : 'arrow-down-right' }}" style="width:11px;height:11px;"></i>
            {{ $delta }}
        </span>
    @endisset
</div>
