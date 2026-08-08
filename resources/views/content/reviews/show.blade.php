@extends('layouts.admin')
@section('title', 'Review Detail')

@section('content')

<x-page-header
    title="Review — {{ $review->tool->name ?? '—' }}"
    subtitle="By {{ $review->user->name ?? 'Anonymous' }} · {{ ucfirst($review->status) }} {{ $review->created_at->format('M j') }}"
    :breadcrumb="['Content', 'Reviews', 'Detail']">
    <x-slot:actions>
        @if ($review->status !== 'published')
        <form action="{{ route('admin.content.reviews.approve', $review->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Approve</button>
        </form>
        @endif
        @if ($review->status !== 'flagged')
        <form action="{{ route('admin.content.reviews.flag', $review->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="flag"></i> Flag</button>
        </form>
        @endif
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center gap-12" style="margin-bottom:14px;">
                <div class="thumb lg">{{ substr($review->tool->name ?? '—', 0, 2) }}</div>
                <div>
                    <b style="font-size:15px;">{{ $review->tool->name ?? '—' }}</b>
                    <div class="cell-sub">Reviewed by {{ $review->user->name ?? 'Anonymous' }}</div>
                </div>
                <div style="margin-left:auto; text-align:right;">
                    <div class="font-display" style="font-size:22px; font-weight:700;">{{ number_format($review->rating, 1) }}</div>
                    <div class="cell-sub">Rating</div>
                </div>
            </div>
            <p style="font-size:13.5px; color:var(--text-md); line-height:1.7;">
                {{ $review->body ?: 'No written review — star rating only.' }}
            </p>
        </div>

        @if (!empty($review->pros) || !empty($review->cons))
        <div class="grid-2">
            <div class="card card-pad">
                <div class="section-title" style="color:var(--pos);">Pros</div>
                <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--text-md); line-height:1.9;">
                    @forelse ($review->pros ?? [] as $pro)
                        <li>{{ $pro }}</li>
                    @empty
                        <li class="text-sub">None listed</li>
                    @endforelse
                </ul>
            </div>
            <div class="card card-pad">
                <div class="section-title" style="color:var(--neg);">Cons</div>
                <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--text-md); line-height:1.9;">
                    @forelse ($review->cons ?? [] as $con)
                        <li>{{ $con }}</li>
                    @empty
                        <li class="text-sub">None listed</li>
                    @endforelse
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-4 card card-pad">
        @if (!empty($review->rating_breakdown))
        <div class="section-title">Ratings Breakdown</div>
        @foreach ($review->rating_breakdown as $label => $val)
        <div style="margin-bottom:12px;">
            <div class="flex items-center justify-between" style="margin-bottom:5px;"><span class="text-sub" style="font-size:12.5px;">{{ $label }}</span><span class="mono" style="font-size:12.5px;">{{ $val }}</span></div>
            <div class="progress"><span style="width:{{ $val }}%;"></span></div>
        </div>
        @endforeach
        <div class="divider"></div>
        @endif
        <div class="flex items-center justify-between">
            <span class="cell-sub">Status</span>
            <x-status-badge
                status="{{ ucfirst($review->status) }}"
                type="{{ $review->status === 'published' ? 'pos' : ($review->status === 'pending' ? 'warn' : 'neg') }}" />
        </div>
    </div>
</div>
@endsection
