@extends('layouts.admin')
@section('title', 'SEO Health')

@section('content')

<x-page-header title="SEO Health" subtitle="Which published content is missing SEO details" :breadcrumb="['System', 'SEO']" />

<div class="kpi-grid" style="grid-template-columns:repeat(2,1fr); margin-bottom:20px;">
    <x-kpi-card icon="wrench" label="Tools Missing SEO" value="{{ $toolsMissing->count() }} / {{ $toolsTotal }}" />
    <x-kpi-card icon="file-text" label="Articles Missing SEO" value="{{ $articlesMissing->count() }} / {{ $articlesTotal }}" />
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-head"><h3>Tools Missing SEO Title or Meta Description</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>SEO Title</th><th>Meta Description</th><th></th></tr></thead>
        <tbody>
        @forelse ($toolsMissing as $tool)
        <tr>
            <td><b>{{ $tool->name }}</b></td>
            <td>{{ $tool->seo_title ? '✓' : '—' }}</td>
            <td>{{ $tool->meta_description ? '✓' : '—' }}</td>
            <td><a href="{{ route('admin.tools.edit', $tool->id) }}" class="btn btn-secondary btn-sm">Fix Now</a></td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-sub" style="text-align:center; padding:24px;">All published tools have SEO fields filled in.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>Articles Missing SEO Title or Meta Description</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Article</th><th>SEO Title</th><th>Meta Description</th><th></th></tr></thead>
        <tbody>
        @forelse ($articlesMissing as $article)
        <tr>
            <td><b>{{ $article->title }}</b></td>
            <td>{{ $article->seo_title ? '✓' : '—' }}</td>
            <td>{{ $article->meta_description ? '✓' : '—' }}</td>
            <td><a href="{{ route('admin.content.articles.editor.edit', $article->id) }}" class="btn btn-secondary btn-sm">Fix Now</a></td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-sub" style="text-align:center; padding:24px;">All published articles have SEO fields filled in.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
