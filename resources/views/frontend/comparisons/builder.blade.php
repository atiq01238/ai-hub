@extends('frontend.layouts.app')

@section('title', 'Build an AI Comparison | AI Orbit')
@section('meta_description', 'Select 2–4 AI tools or models and compare their pricing, capabilities, benchmark performance and product details side by side.')

@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/frontend/comparisons.js') }}" defer></script>@endpush

@section('content')
<section class="builder-hero">
    <div class="compare-container builder-hero-inner">
        <span class="comparison-kicker"><i data-lucide="git-compare-arrows"></i> Comparison builder</span>
        <h1>Choose what you want to compare.</h1>
        <p>Select between 2 and 4 products. AI Orbit will build a clean side-by-side research view using the data already in your database.</p>
    </div>
</section>

<section class="builder-section" data-comparison-builder>
    <div class="compare-container">
        <div class="builder-type-switch" role="tablist">
            <button type="button" class="{{ $type === 'tool' ? 'active' : '' }}" data-builder-type="tool"><i data-lucide="bot"></i><strong>AI Tools</strong><span>Compare products and platforms</span></button>
            <button type="button" class="{{ $type === 'model' ? 'active' : '' }}" data-builder-type="model"><i data-lucide="cpu"></i><strong>AI Models</strong><span>Compare model intelligence and API data</span></button>
        </div>

        <div class="builder-workspace">
            <div class="builder-catalog">
                <div class="builder-catalog-head"><div><h2>Select products</h2><p>Pick 2–4 items for your comparison.</p></div><label><i data-lucide="search"></i><input type="search" placeholder="Search products..." data-builder-search></label></div>

                <div class="builder-products {{ $type === 'tool' ? '' : 'hidden' }}" data-builder-panel="tool">
                    @foreach($tools as $tool)
                        <button type="button" class="builder-product-card" data-builder-item data-type="tool" data-id="{{ $tool->id }}" data-search="{{ strtolower($tool->name.' '.($tool->company->name ?? '').' '.($tool->short_description ?? '')) }}">
                            <span class="builder-product-logo"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"></span>
                            <span class="builder-product-copy"><small>{{ $tool->company->name ?? 'Independent' }}</small><strong>{{ $tool->name }}</strong><em>★ {{ number_format((float)$tool->rating,1) }} · Benchmark {{ $tool->benchmark_score !== null ? number_format((float)$tool->benchmark_score,1) : '—' }}</em></span>
                            <span class="select-indicator"><i data-lucide="plus"></i></span>
                        </button>
                    @endforeach
                </div>

                <div class="builder-products {{ $type === 'model' ? '' : 'hidden' }}" data-builder-panel="model">
                    @foreach($models as $model)
                        <button type="button" class="builder-product-card" data-builder-item data-type="model" data-id="{{ $model->id }}" data-search="{{ strtolower($model->name.' '.($model->company->name ?? '').' '.($model->version ?? '')) }}">
                            <span class="builder-product-logo"><img src="{{ $model->logo_url }}" alt="{{ $model->name }} logo"></span>
                            <span class="builder-product-copy"><small>{{ $model->company->name ?? 'Independent' }}</small><strong>{{ $model->name }}</strong><em>{{ $model->context_window ?: 'Context N/A' }} · Benchmark {{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</em></span>
                            <span class="select-indicator"><i data-lucide="plus"></i></span>
                        </button>
                    @endforeach
                </div>
            </div>

            <aside class="builder-selection">
                <div class="selection-head"><span><i data-lucide="layers-3"></i> Your comparison</span><strong data-selection-count>0 / 4</strong></div>
                <div class="selection-slots" data-selection-slots>
                    @for($i=1;$i<=4;$i++)<div class="selection-slot empty"><span>{{ $i }}</span><div><strong>Select a product</strong><small>{{ $i <= 2 ? 'Required' : 'Optional' }}</small></div></div>@endfor
                </div>
                <form action="{{ route('comparisons.preview') }}" method="get" data-compare-form>
                    <input type="hidden" name="type" value="{{ $type }}" data-form-type>
                    <div data-form-items></div>
                    <button type="submit" class="build-result-btn" disabled data-build-button><i data-lucide="sparkles"></i> Compare selected products</button>
                </form>
                <p class="builder-note"><i data-lucide="info"></i> Select at least two products. You can compare up to four at once.</p>
            </aside>
        </div>
    </div>
</section>
@endsection
