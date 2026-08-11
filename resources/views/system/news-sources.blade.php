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
                <option value="api">API</option>
            </select>
        </div>
        <div class="form-field" style="flex:1; min-width:220px;"><label>URL</label><input class="input" name="url" placeholder="https://" required></div>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Source</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Source</th><th>Type</th><th>URL</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($sources as $source)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($source->name, 0, 2) }}</div><b>{{ $source->name }}</b></div></td>
            <td><span class="badge badge-neutral">{{ strtoupper($source->type) }}</span></td>
            <td class="text-sub" style="font-size:12px;">{{ $source->url }}</td>
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
        <tr><td colspan="5" class="text-sub" style="text-align:center; padding:32px;">No sources yet — add one above.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    This is just your source list — "Last Fetched", "Articles Collected", and "Reliability"
    columns from the original design aren't here because nothing automated is actually pulling
    from these sources yet. Once News API integration is built, this list becomes what it
    fetches from, and those stats become real.
</p>
@endsection
