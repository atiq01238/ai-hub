{{-- Props: $title, $subtitle (optional), $breadcrumb (optional array) --}}
<div class="page-header">
    <div class="page-header__copy">
        @isset($breadcrumb)
            <nav class="breadcrumb" aria-label="Breadcrumb">
                @foreach($breadcrumb as $crumb)
                    <span>{{ $crumb }}</span>
                    @if(!$loop->last)<i data-lucide="chevron-right" aria-hidden="true"></i>@endif
                @endforeach
            </nav>
        @endisset
        <h1 class="page-title">{{ $title }}</h1>
        @isset($subtitle)
            @if(filled($subtitle))<div class="page-subtitle">{{ $subtitle }}</div>@endif
        @endisset
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</div>
