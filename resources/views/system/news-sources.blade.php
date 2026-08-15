@extends('layouts.admin')
@section('title', 'News Source Management')

@section('content')

<x-page-header title="News Source Management" subtitle="{{ $sources->count() }} sources on your list" :breadcrumb="['AI Intelligence', 'News Sources']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card card-pad" style="margin-bottom:16px;">
    <form action="{{ route('admin.system.news-sources.store') }}" method="POST" class="flex gap-8" style="align-items:flex-end; flex-wrap:wrap;">
        @csrf
        <div class="form-field"><label>Source Name</label><input class="input" name="name" placeholder="e.g. TechCrunch AI" required></div>
        <div class="form-field"><label>Type</label>
            <select class="select" name="type">
                <option value="rss">RSS</option>
                <option value="api">API (not wired up yet)</option>
            </select>
        </div>
        <div class="form-field" style="flex:1; min-width:220px;"><label>Feed URL</label><input class="input" name="url" placeholder="https://example.com/feed.xml" required></div>
        <div class="form-field"><label>Default Category</label>
            <select class="select" name="default_category">
                <option value="">None</option>
                <option>Breaking News</option>
                <option>Product Launch</option>
                <option>Product Update</option>
                <option>Research</option>
                <option>Pricing Change</option>
            </select>
        </div>
        <div class="form-field"><label>Company (optional)</label>
            <select class="select" name="company_id">
                <option value="">Auto-detect</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Source</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Source</th><th>Type</th><th>URL</th><th>Last Fetched</th><th>Articles Collected</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($sources as $source)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($source->name, 0, 2) }}</div><b>{{ $source->name }}</b></div></td>
            <td><span class="badge badge-neutral">{{ strtoupper($source->type) }}</span></td>
            <td class="text-sub" style="font-size:12px;">{{ $source->url }}</td>
            <td class="text-sub" style="font-size:12px;">
                @if ($source->last_fetched_at)
                    {{ $source->last_fetched_at->diffForHumans() }}
                    @if ($source->last_error)
                        <div style="color:var(--neg);">⚠ {{ Str::limit($source->last_error, 40) }}</div>
                    @endif
                @else
                    Never
                @endif
            </td>
            <td class="mono">{{ $source->articles_collected }}</td>
            <td>
                <form action="{{ route('admin.system.news-sources.toggle', $source->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;">
                        <x-status-badge status="{{ ucfirst($source->status) }}" type="{{ $source->status === 'active' ? 'pos' : 'neutral' }}" />
                    </button>
                </form>
            </td>
            <td>
                <form action="{{ route('admin.system.news-sources.destroy', $source->id) }}" method="POST" onsubmit="return confirm('Remove this source?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-sub" style="text-align:center; padding:32px;">No sources yet — add one above.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    "Last Fetched" and "Articles Collected" update automatically every time <code>php artisan news:fetch</code>
    runs (every 30 minutes via the scheduler, or on-demand from the "Fetch Now" button on the News page).
    Only RSS sources are live right now — API-type sources are listed but not fetched yet.
</p>
@endsection