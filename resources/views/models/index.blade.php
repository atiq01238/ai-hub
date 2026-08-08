@extends('layouts.admin')
@section('title', 'AI Models')

@section('content')

<x-page-header title="AI Model Management" subtitle="{{ $models->total() }} models tracked" :breadcrumb="['AI Management', 'AI Models']">
    <x-slot:actions>
        <a href="{{ route('admin.models.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Model</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i><input type="text" placeholder="Search models...">
    </div>
    <select class="select"><option>All Companies</option></select>
    <select class="select"><option>All Tools</option></select>
    <select class="select"><option>Modality: Any</option></select>
    <select class="select"><option>Status: Any</option><option>Active</option><option>Deprecated</option><option>Preview</option></select>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Model</th><th>Company</th><th>Version</th><th>Context</th><th>Input $/1M</th><th>Output $/1M</th><th>Benchmark</th><th>Capabilities</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($models as $model)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($model->name, 0, 2) }}</div><a href="{{ route('admin.models.show', $model->id) }}"><b>{{ $model->name }}</b></a></div></td>
            <td class="text-sub">{{ $model->company->name ?? '—' }}</td>
            <td class="mono text-sub">{{ $model->version ?? '—' }}</td>
            <td class="mono">{{ $model->context_window ?? '—' }}</td>
            <td class="mono">{{ $model->input_price_per_million !== null ? '$'.number_format($model->input_price_per_million, 2) : '—' }}</td>
            <td class="mono">{{ $model->output_price_per_million !== null ? '$'.number_format($model->output_price_per_million, 2) : '—' }}</td>
            <td><x-score-meter :value="(int) $model->benchmark_score" :segments="5" /></td>
            <td>
                <div class="flex gap-8">
                    @foreach (array_slice($model->capabilities ?? [], 0, 3) as $c)<span class="badge badge-violet">{{ $c }}</span>@endforeach
                </div>
            </td>
            <td><x-status-badge status="{{ ucfirst($model->status) }}" type="{{ $model->status === 'active' ? 'pos' : ($model->status === 'preview' ? 'warn' : 'neutral') }}" /></td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.models.edit', $model->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.models.destroy', $model->id) }}" method="POST" onsubmit="return confirm('Delete this model?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" class="text-sub" style="text-align:center; padding:32px;">No models yet — add your first one.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $models->firstItem() ?? 0 }}–{{ $models->lastItem() ?? 0 }} of {{ $models->total() }} models</span>
        <div class="pager-btns">{{ $models->links() }}</div>
    </div>
</div>

@endsection
