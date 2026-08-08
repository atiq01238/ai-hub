@extends('layouts.admin')
@section('title', 'News Articles')

@section('content')

<x-page-header title="Content Management System" subtitle="News articles, drafts, and scheduled content" :breadcrumb="['Content', 'News Articles']">
    <x-slot:actions><a href="{{ url('/content/articles/editor') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Create Article</a></x-slot:actions>
</x-page-header>

<div class="tabs">
    <div class="tab is-active">All</div>
    <div class="tab">Drafts</div>
    <div class="tab">Published</div>
    <div class="tab">Scheduled</div>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Published</th></tr></thead>
        <tbody>
            <tr><td><b>GPT-5.2 Turbo Explained: What Changes for Developers</b></td><td class="text-sub">New Model</td><td class="text-sub">Sarah A.</td><td><x-status-badge status="Published" type="pos" /></td><td class="cell-sub">Aug 5, 9:00 AM</td></tr>
            <tr><td><b>Anthropic's Enterprise API Push, Explained</b></td><td class="text-sub">Product Update</td><td class="text-sub">Imran K.</td><td><x-status-badge status="In Review" type="warn" /></td><td class="cell-sub">—</td></tr>
            <tr><td><b>Every AI Pricing Change This Week</b></td><td class="text-sub">Pricing Change</td><td class="text-sub">Sarah A.</td><td><x-status-badge status="Draft" type="neutral" /></td><td class="cell-sub">—</td></tr>
            <tr><td><b>Is Gemini 3 Pro the New Reasoning Leader?</b></td><td class="text-sub">Benchmark</td><td class="text-sub">Ayesha R.</td><td><x-status-badge status="Scheduled" type="info" /></td><td class="cell-sub">Aug 7, 8:00 AM</td></tr>
        </tbody>
    </table>
    </div>
</div>
@endsection
