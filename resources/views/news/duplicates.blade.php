@extends('layouts.admin')
@section('title', 'Duplicate News Detection')
@section('content')
<x-page-header title="Duplicate News Detection" subtitle="Cross-source similarity results" :breadcrumb="['AI Intelligence', 'Duplicate Detection']">
    <x-slot:actions><a href="{{ url('/admin/news') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> Back to News Feed</a></x-slot:actions>
</x-page-header>

@if(!empty($notice))<div class="alert alert-info">{{ $notice }}</div>@endif

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <x-kpi-card icon="copy" label="Confirmed Duplicates" value="{{ $stats['confirmed'] ?? 0 }}" />
    <x-kpi-card icon="clock" label="Possible Duplicates" value="{{ $stats['possible'] ?? 0 }}" />
    <x-kpi-card icon="check-circle" label="Unique" value="{{ $stats['unique'] ?? 0 }}" />
    <x-kpi-card icon="layers" label="Duplicate Results" value="{{ $stats['total'] ?? 0 }}" />
</div>

<div class="card card-pad">
@if($groups instanceof \Illuminate\Pagination\LengthAwarePaginator && $groups->count())
    @foreach($groups as $group)
        <div style="padding:14px 0; border-bottom:1px solid var(--border-soft);">
            @if(isset($group->primary_headline))
                <div style="font-weight:650;">{{ $group->primary_headline }}</div>
                <div class="cell-sub">Primary: {{ $group->primary_source ?? 'Unknown' }} · {{ $group->article_count }} article(s) · {{ ucfirst($group->status) }}</div>
            @else
                <div style="font-weight:650;">{{ $group->headline }}</div>
                <div class="cell-sub">{{ $group->source ?? 'Unknown' }} · {{ $group->duplicate_score ?? 0 }}% similarity · {{ ucfirst($group->duplicate_status ?? 'duplicate') }}</div>
            @endif
        </div>
    @endforeach
    <div style="margin-top:16px;">{{ $groups->links() }}</div>
@elseif($groups instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="text-sub" style="text-align:center;padding:32px;">No duplicate groups found. Run <code>php artisan news:duplicates --all</code>.</div>
@endif
</div>
@endsection
