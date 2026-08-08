@extends('layouts.admin')
@section('title', 'AI Reviews')

@section('content')

<x-page-header title="AI Review Management" subtitle="{{ $reviews->total() }} reviews · {{ $reviews->where('status', 'pending')->count() }} awaiting moderation on this page" :breadcrumb="['Content', 'Reviews']">
    <x-slot:actions><a href="{{ route('admin.content.reviews.editor') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Review</a></x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <div class="tab is-active">All Reviews</div>
    <div class="tab">Pending Moderation</div>
    <div class="tab">Flagged</div>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Reviewer</th><th>Rating</th><th>Verdict</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($reviews as $review)
            <tr>
                <td>
                    <div class="row-media">
                        <div class="thumb">{{ substr($review->tool->name ?? '—', 0, 2) }}</div>
                        <a href="{{ route('admin.content.reviews.show', $review->id) }}"><b>{{ $review->tool->name ?? '—' }}</b></a>
                    </div>
                </td>
                <td class="text-sub">{{ $review->user->name ?? 'Anonymous' }}</td>
                <td class="mono"><i data-lucide="star" style="width:12px;height:12px;color:var(--warn);vertical-align:-2px;"></i> {{ number_format($review->rating, 1) }}</td>
                <td class="text-sub">{{ $review->verdict ?? \Illuminate\Support\Str::limit($review->body, 50) }}</td>
                <td class="cell-sub">{{ $review->created_at->format('M j') }}</td>
                <td>
                    <x-status-badge
                        status="{{ ucfirst($review->status) }}"
                        type="{{ $review->status === 'published' ? 'pos' : ($review->status === 'pending' ? 'warn' : 'neg') }}" />
                </td>
                <td>
                    <div class="flex gap-8">
                        <a href="{{ route('admin.content.reviews.show', $review->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                        @if ($review->status !== 'published')
                        <form action="{{ route('admin.content.reviews.approve', $review->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">Approve</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-sub" style="text-align:center; padding:32px;">No reviews yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $reviews->firstItem() ?? 0 }}–{{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }} reviews</span>
        <div class="pager-btns">{{ $reviews->links() }}</div>
    </div>
</div>
@endsection
