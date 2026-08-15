@extends('layouts.admin')
@section('title', 'Data Verification Center')

@section('content')
<x-page-header title="Data Verification Center" subtitle="{{ $items->total() }} news item(s) waiting for verification" :breadcrumb="['System', 'Data Verification']" />

@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>News</th><th>Source</th><th>Importance</th><th>AI Confidence</th><th>Published</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($items as $item)
        <tr>
            <td><b>{{ $item->headline }}</b><div class="cell-sub">{{ \Illuminate\Support\Str::limit($item->summary, 90) }}</div></td>
            <td class="text-sub">{{ $item->newsSource?->name ?? $item->source ?? 'Unknown' }}</td>
            <td><x-score-meter :value="$item->importance" :segments="5" /></td>
            <td class="mono">{{ $item->ai_confidence !== null ? $item->ai_confidence.'%' : '—' }}</td>
            <td class="cell-sub">{{ $item->published_at?->diffForHumans() ?? 'Unknown' }}</td>
            <td><x-status-badge :status="ucwords(str_replace('_',' ', $item->verification_status))" :type="$item->verification_status === 'needs_verification' ? 'warn' : 'neutral'" /></td>
            <td>
                <div class="flex gap-8">
                    <form method="POST" action="{{ route('admin.system.data-verification.verify', $item->id) }}">@csrf<button class="btn btn-secondary btn-sm">Verify</button></form>
                    <form method="POST" action="{{ route('admin.system.data-verification.needs-verification', $item->id) }}">@csrf<button class="btn btn-ghost btn-sm">Needs Review</button></form>
                    <a class="btn btn-ghost btn-sm" href="{{ route('admin.news.edit', $item->id) }}">Edit</a>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-sub" style="text-align:center;padding:32px;">No news items currently require verification.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($items->hasPages())<div style="margin-top:16px;">{{ $items->links() }}</div>@endif
@endsection
