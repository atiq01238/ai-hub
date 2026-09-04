@extends('layouts.admin')
@section('title','SEO Health')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}?v=20260904-seo-health-v4">@endpush
@section('content')
@php
    $summary = $seoHealth['summary'];
    $semantic = $seoHealth['semantic'];
    $foundationScore = $summary['hard_conflicts'] === 0 ? 100 : max(0, 100 - min(100, $summary['hard_conflicts'] * 5));
@endphp
<div class="fp-page fp-seo-health-v4">
    <x-page-header title="SEO Health" subtitle="One control center for intent ownership, metadata alignment, semantic links and relationship hygiene." :breadcrumb="['System','SEO']" />

    <section class="fp-seo-hero">
        <div class="fp-health-orb" style="--score:{{ $foundationScore }}"><strong>{{ $foundationScore }}</strong><span>% healthy</span></div>
        <div>
            <span class="fp-eyebrow">AI Orbit SEO Foundation</span>
            <h1>{{ $summary['hard_conflicts'] === 0 ? 'Intent, metadata and link safety are aligned' : $summary['hard_conflicts'].' hard SEO conflict(s) need review' }}</h1>
            <p>This dashboard uses the same Phase 1 intent inventory, Phase 2 metadata generator and Phase 3 semantic-link rules that power the public site.</p>
            <div class="fp-seo-command-row"><code>php artisan seo:audit-health --details</code><code>php artisan seo:audit-intent-map --details</code></div>
        </div>
    </section>

    <section class="fp-kpis fp-seo-kpis-v4">
        <article class="fp-kpi"><span><i data-lucide="target"></i></span><div><small>Keyword intent coverage</small><strong>{{ number_format($summary['intent_coverage'],1) }}%</strong><em>{{ $summary['persisted_targets'] }}/{{ $summary['intent_total'] }} persisted</em></div></article>
        <article class="fp-kpi"><span><i data-lucide="scan-text"></i></span><div><small>Metadata alignment</small><strong>{{ number_format($summary['metadata_coverage'],1) }}%</strong><em>{{ $summary['metadata_aligned'] }}/{{ $summary['metadata_total'] }} aligned</em></div></article>
        <article class="fp-kpi {{ $summary['hard_conflicts'] ? 'fp-kpi--amber' : '' }}"><span><i data-lucide="shield-check"></i></span><div><small>Hard SEO conflicts</small><strong>{{ number_format($summary['hard_conflicts']) }}</strong><em>{{ $summary['hard_conflicts'] ? 'Review before deploy' : 'Safe foundation' }}</em></div></article>
        <article class="fp-kpi {{ $semantic['hygiene_total'] ? 'fp-kpi--amber' : '' }}"><span><i data-lucide="git-branch"></i></span><div><small>Stored pivot warnings</small><strong>{{ number_format($semantic['hygiene_total']) }}</strong><em>Filtered from public output</em></div></article>
    </section>

    <section class="fp-seo-foundation-grid">
        <article class="card fp-seo-foundation-card">
            <header><span><i data-lucide="crosshair"></i></span><div><small>PHASE 1</small><h2>Search intent ownership</h2></div><b class="{{ $summary['missing_primary'] || $summary['primary_collisions'] ? 'is-warn' : 'is-good' }}">{{ $summary['missing_primary'] || $summary['primary_collisions'] ? 'Review' : 'Clean' }}</b></header>
            <dl><div><dt>Missing primary keywords</dt><dd>{{ $summary['missing_primary'] }}</dd></div><div><dt>Primary collision groups</dt><dd>{{ $summary['primary_collisions'] }}</dd></div><div><dt>Stale persisted targets</dt><dd>{{ $summary['stale_targets'] }}</dd></div></dl>
        </article>
        <article class="card fp-seo-foundation-card">
            <header><span><i data-lucide="file-search-2"></i></span><div><small>PHASE 2</small><h2>Live search metadata</h2></div><b class="{{ $summary['metadata_misaligned'] || $summary['metadata_errors'] ? 'is-warn' : 'is-good' }}">{{ $summary['metadata_misaligned'] || $summary['metadata_errors'] ? 'Review' : 'Clean' }}</b></header>
            <dl><div><dt>Missing titles</dt><dd>{{ $summary['missing_titles'] }}</dd></div><div><dt>Missing descriptions</dt><dd>{{ $summary['missing_descriptions'] }}</dd></div><div><dt>Duplicate title groups</dt><dd>{{ $summary['duplicate_titles'] }}</dd></div><div><dt>Generation errors</dt><dd>{{ $summary['metadata_errors'] }}</dd></div></dl>
        </article>
        <article class="card fp-seo-foundation-card">
            <header><span><i data-lucide="network"></i></span><div><small>PHASE 3</small><h2>Semantic-link safety</h2></div><b class="{{ $semantic['unsafe_comparison_links'] ? 'is-warn' : 'is-good' }}">{{ $semantic['unsafe_comparison_links'] ? 'Review' : 'Safe' }}</b></header>
            <dl><div><dt>Unsafe generated comparison links</dt><dd>{{ $semantic['unsafe_comparison_links'] }}</dd></div><div><dt>Valid published comparisons</dt><dd>{{ $semantic['comparison_count'] }}</dd></div><div><dt>Tools without explicit editorial edge</dt><dd>{{ $semantic['sparse_tools_count'] }}</dd></div><div><dt>Models without explicit editorial edge</dt><dd>{{ $semantic['sparse_models_count'] }}</dd></div></dl>
        </article>
    </section>

    <section class="card fp-table-card fp-seo-coverage-card">
        <header class="fp-card-head"><div><span class="fp-eyebrow">Semantic Discovery</span><h2>Internal-link coverage</h2><p>Coverage is diagnostic, not a quota. AI Orbit intentionally leaves a slot empty instead of inserting unrelated content.</p></div><span class="fp-count"><i data-lucide="link-2"></i></span></header>
        <div class="table-wrap"><table class="data-table fp-table"><thead><tr><th>Discovery path</th><th>Covered</th><th>Eligible</th><th>Coverage</th><th>Signal</th></tr></thead><tbody>
        @foreach($semantic['coverage'] as $row)
            @php $pct = $row['eligible'] ? round(($row['covered'] / $row['eligible']) * 100, 1) : 100; @endphp
            <tr><td><strong>{{ $row['label'] }}</strong></td><td>{{ number_format($row['covered']) }}</td><td>{{ number_format($row['eligible']) }}</td><td><div class="fp-seo-mini-meter"><i style="width:{{ min(100,$pct) }}%"></i></div><small>{{ number_format($pct,1) }}%</small></td><td><span class="fp-field-state {{ $row['covered'] ? 'is-good' : 'is-missing' }}"><i data-lucide="{{ $row['covered'] ? 'link' : 'circle-dashed' }}"></i>{{ $row['covered'] ? 'Available' : 'No explicit edge' }}</span></td></tr>
        @endforeach
        </tbody></table></div>
    </section>

    <div class="fp-seo-grid fp-seo-cleanup-grid">
        <section class="card fp-table-card">
            <header class="fp-card-head"><div><span class="fp-eyebrow">Relationship Hygiene</span><h2>Stored pivots filtered from public output</h2><p>These are cleanup signals only. Phase 3 already prevents non-public entities from being exposed.</p></div><span class="fp-count">{{ $semantic['hygiene_total'] }}</span></header>
            <div class="fp-seo-hygiene-list">
                @foreach([
                    ['Approved article → non-public tools',$semantic['hygiene']['article_non_public_tools']],
                    ['Approved article → non-public models',$semantic['hygiene']['article_non_public_models']],
                    ['Public news → non-public tools',$semantic['hygiene']['news_non_public_tools']],
                    ['Public news → non-public models',$semantic['hygiene']['news_non_public_models']],
                ] as [$label,$count])
                <div><span><i data-lucide="{{ $count ? 'triangle-alert' : 'check-circle-2' }}"></i>{{ $label }}</span><strong class="{{ $count ? 'is-warn' : 'is-good' }}">{{ $count }}</strong></div>
                @endforeach
            </div>
        </section>

        <section class="card fp-table-card">
            <header class="fp-card-head"><div><span class="fp-eyebrow">Editorial Opportunity</span><h2>Why sparse edges are not errors</h2><p>These pages still keep contextual company/category/provider links. Add editorial links only when the content is genuinely relevant.</p></div><span class="fp-count"><i data-lucide="sparkles"></i></span></header>
            <div class="fp-seo-opportunity-copy"><p><strong>{{ $semantic['sparse_tools_count'] }}</strong> published tools and <strong>{{ $semantic['sparse_models_count'] }}</strong> public models currently have no explicit article, news or comparison edge.</p><p>Do not fill these with random “popular” content. Publish or connect a real guide, update or comparison first.</p></div>
        </section>
    </div>

    <div class="fp-seo-grid fp-seo-sparse-grid">
        <section class="card fp-table-card"><header class="fp-card-head"><div><span class="fp-eyebrow">Tools</span><h2>Sample pages needing editorial edges</h2><p>Top sample only; contextual catalog links remain intact.</p></div><span class="fp-count">{{ $semantic['sparse_tools_count'] }}</span></header>
            @if($semantic['sparse_tools']->isNotEmpty())<div class="table-wrap"><table class="data-table fp-table"><thead><tr><th>Tool</th><th>Public page</th><th>Admin</th></tr></thead><tbody>@foreach($semantic['sparse_tools'] as $tool)<tr><td><strong>{{ $tool->name }}</strong><small>/{{ $tool->slug }}</small></td><td><a href="{{ route('tools.show',$tool) }}" target="_blank" rel="noopener">View <i data-lucide="arrow-up-right"></i></a></td><td><a href="{{ route('admin.tools.edit',$tool->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="pencil"></i>Edit</a></td></tr>@endforeach</tbody></table></div>@else<div class="fp-empty fp-empty--small"><p>Every published tool has at least one explicit editorial/comparison edge.</p></div>@endif
        </section>
        <section class="card fp-table-card"><header class="fp-card-head"><div><span class="fp-eyebrow">Models</span><h2>Sample pages needing editorial edges</h2><p>Use provider/taxonomy context until a real editorial edge exists.</p></div><span class="fp-count">{{ $semantic['sparse_models_count'] }}</span></header>
            @if($semantic['sparse_models']->isNotEmpty())<div class="table-wrap"><table class="data-table fp-table"><thead><tr><th>Model</th><th>Public page</th><th>Admin</th></tr></thead><tbody>@foreach($semantic['sparse_models'] as $model)<tr><td><strong>{{ $model->name }}</strong><small>/{{ $model->slug }}</small></td><td><a href="{{ route('models.show',$model) }}" target="_blank" rel="noopener">View <i data-lucide="arrow-up-right"></i></a></td><td><a href="{{ route('admin.models.edit',$model->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="pencil"></i>Edit</a></td></tr>@endforeach</tbody></table></div>@else<div class="fp-empty fp-empty--small"><p>Every public model has at least one explicit editorial/comparison edge.</p></div>@endif
        </section>
    </div>
</div>
@endsection
