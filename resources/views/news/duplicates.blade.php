@extends('layouts.admin')
@section('title', 'Duplicate News Detection')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/news.css') }}">
@endpush

@section('content')
<div class="news-shell">
    <x-page-header
        title="Duplicate News Detection"
        subtitle="Review similarity signals and duplicate relationships across collected intelligence."
        :breadcrumb="['AI Intelligence', 'News', 'Duplicates']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.news.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> News Feed</a>
        </x-slot:actions>
    </x-page-header>

    @if(!empty($notice))
        <div class="alert alert-info news-alert"><i data-lucide="info"></i><span>{{ $notice }}</span></div>
    @endif

    <div class="news-duplicate-kpis">
        <div class="news-mini-kpi"><div class="news-mini-kpi__icon is-red"><i data-lucide="copy-x"></i></div><div><span>Confirmed</span><strong>{{ number_format($stats['confirmed'] ?? 0) }}</strong></div></div>
        <div class="news-mini-kpi"><div class="news-mini-kpi__icon is-amber"><i data-lucide="scan-search"></i></div><div><span>Possible</span><strong>{{ number_format($stats['possible'] ?? 0) }}</strong></div></div>
        <div class="news-mini-kpi"><div class="news-mini-kpi__icon is-green"><i data-lucide="badge-check"></i></div><div><span>Unique</span><strong>{{ number_format($stats['unique'] ?? 0) }}</strong></div></div>
        <div class="news-mini-kpi"><div class="news-mini-kpi__icon is-blue"><i data-lucide="layers-3"></i></div><div><span>Results</span><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div></div>
    </div>

    <section class="news-panel">
        <div class="news-panel__header">
            <div class="news-panel__icon"><i data-lucide="copy-check"></i></div>
            <div><h2>Similarity Results</h2><p>Potential and confirmed duplicates ordered by the latest duplicate check.</p></div>
        </div>

        <div class="news-duplicate-list">
            @if($groups instanceof \Illuminate\Pagination\LengthAwarePaginator && $groups->count())
                @foreach($groups as $group)
                    @php
                        $headline = $group->primary_headline ?? $group->headline;
                        $source = $group->primary_source ?? $group->source ?? 'Unknown source';
                        $status = $group->status ?? $group->duplicate_status ?? 'duplicate';
                        $score = $group->duplicate_score ?? null;
                    @endphp
                    <article class="news-duplicate-row">
                        <div class="news-duplicate-row__icon"><i data-lucide="copy"></i></div>
                        <div class="news-duplicate-row__content">
                            <a href="{{ isset($group->id) ? route('admin.news.show', $group->id) : '#' }}">{{ $headline }}</a>
                            <div class="news-duplicate-row__meta">
                                <span>{{ $source }}</span>
                                @if(isset($group->article_count))<span>{{ $group->article_count }} article(s)</span>@endif
                                @if($score !== null)<span>{{ $score }}% similarity</span>@endif
                            </div>
                        </div>
                        <span class="badge {{ $status === 'possible' ? 'badge-warn' : 'badge-neg' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </article>
                @endforeach

                <div class="news-native-pagination">{{ $groups->links() }}</div>
            @else
                <div class="news-empty news-empty--compact">
                    <div class="news-empty__icon"><i data-lucide="copy-check"></i></div>
                    <h3>No duplicate results found</h3>
                    <p>Run <code>php artisan news:duplicates --all</code> after the duplicate detection fields are installed.</p>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
