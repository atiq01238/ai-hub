@extends('layouts.admin')
@section('title','Data Verification Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-reliability.css') }}">
@endpush

@section('content')
<div class="dr-page">
<x-page-header title="Data Verification Center" :subtitle="$items->total().' news item(s) currently require human verification.'" :breadcrumb="['System','Data Verification']">
<x-slot:actions><a href="{{ route('admin.system.source-reliability') }}" class="btn btn-secondary"><i data-lucide="shield-check"></i>Source Reliability</a></x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success dr-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="dr-verification-hero">
<span><i data-lucide="badge-help"></i></span><div><span class="dr-eyebrow">Human Verification Queue</span><strong>{{ number_format($items->total()) }} items need attention</strong><p>Prioritize high-importance items and low AI-confidence records before they become trusted intelligence.</p></div>
</section>

<section class="card dr-table-card">
<header class="dr-card-head"><div><span class="dr-eyebrow">Verification Queue</span><h2>Unverified intelligence</h2><p>Sorted by verification need, importance and publication time.</p></div><span class="dr-count">{{ number_format($items->total()) }} items</span></header>
@if($items->count())
<div class="table-wrap"><table class="data-table dr-table dr-verification-table"><thead><tr><th>News Intelligence</th><th>Source</th><th>Importance</th><th>AI Confidence</th><th>Published</th><th>State</th><th>Decision</th></tr></thead><tbody>
@foreach($items as $item)
<tr>
<td><div class="dr-record dr-record--news"><span><i data-lucide="newspaper"></i></span><div><strong>{{ $item->headline }}</strong><small>{{ \Illuminate\Support\Str::limit($item->summary,95) }}</small></div></div></td>
<td><div class="dr-source-cell"><strong>{{ $item->newsSource?->name ?? $item->source ?? 'Unknown' }}</strong>@if($item->company)<small>{{ $item->company->name }}</small>@endif</div></td>
<td><div class="dr-score"><strong>{{ $item->importance }}/100</strong><div><span style="width:{{ min(100,max(0,$item->importance)) }}%"></span></div></div></td>
<td><span class="dr-confidence {{ $item->ai_confidence !== null && $item->ai_confidence < 70 ? 'is-low':'' }}">{{ $item->ai_confidence !== null ? $item->ai_confidence.'%' : '—' }}</span></td>
<td><span class="dr-muted">{{ $item->published_at?->diffForHumans() ?? 'Unknown' }}</span></td>
<td><x-status-badge :status="ucwords(str_replace('_',' ',$item->verification_status))" :type="$item->verification_status==='needs_verification'?'warn':'neutral'" /></td>
<td><div class="dr-actions dr-actions--verify"><form method="POST" action="{{ route('admin.system.data-verification.verify',$item->id) }}">@csrf<button class="btn btn-primary btn-sm"><i data-lucide="badge-check"></i>Verify</button></form><form method="POST" action="{{ route('admin.system.data-verification.needs-verification',$item->id) }}">@csrf<button class="btn btn-secondary btn-sm"><i data-lucide="search"></i>Needs Review</button></form><a class="icon-btn" href="{{ route('admin.news.edit',$item->id) }}" title="Edit record"><i data-lucide="pencil"></i></a></div></td>
</tr>
@endforeach
</tbody></table></div>
<div class="dr-pagination"><span>Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}</span><div>{{ $items->links() }}</div></div>
@else<div class="dr-empty"><span><i data-lucide="badge-check"></i></span><h3>Verification queue is clear</h3><p>No news items currently require human verification.</p></div>@endif
</section>
</div>
@endsection
