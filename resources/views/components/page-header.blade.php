{{-- Props: $title, $subtitle (optional), $breadcrumb (optional array) --}}
<div class="page-header">
    <div>
        @isset($breadcrumb)
            <div class="breadcrumb">
                @foreach($breadcrumb as $i => $crumb)
                    <span>{{ $crumb }}</span>
                    @if(!$loop->last)<i data-lucide="chevron-right" style="width:12px;height:12px;"></i>@endif
                @endforeach
            </div>
        @endisset
        <h1 class="page-title">{{ $title }}</h1>
        @isset($subtitle)<div class="page-subtitle">{{ $subtitle }}</div>@endisset
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</div>
