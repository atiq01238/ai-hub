@extends('layouts.admin')
@section('title', 'Taxonomy')

@section('content')
@php
    $tabLabels = ['categories'=>'Categories','subcategories'=>'Subcategories','features'=>'Features','tags'=>'Tags'];
@endphp

<x-page-header title="Categories, Features &amp; Tags" :breadcrumb="['AI Management', $tabLabels[$tab]]" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="tabs">
    <a href="{{ route('admin.taxonomy.categories') }}" class="tab {{ $tab==='categories'?'is-active':'' }}">Categories</a>
    <a href="{{ route('admin.taxonomy.subcategories') }}" class="tab {{ $tab==='subcategories'?'is-active':'' }}">Subcategories</a>
    <a href="{{ route('admin.taxonomy.features') }}" class="tab {{ $tab==='features'?'is-active':'' }}">Features</a>
    <a href="{{ route('admin.taxonomy.tags') }}" class="tab {{ $tab==='tags'?'is-active':'' }}">Tags</a>
</div>

<div class="card card-pad" style="margin-bottom:16px;">
    <form action="{{ route('admin.taxonomy.store', $tab) }}" method="POST" class="flex gap-8">
        @csrf
        <input class="input" name="name" placeholder="New {{ rtrim($tabLabels[$tab], 's') }} name..." required style="flex:1;">
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add {{ rtrim($tabLabels[$tab], 's') }}</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Usage Count</th><th></th></tr></thead>
        <tbody>
        @forelse ($terms as $term)
        <tr>
            <td>
                <form action="{{ route('admin.taxonomy.update', [$tab, $term->id]) }}" method="POST" class="flex gap-8">
                    @csrf
                    @method('PUT')
                    <input class="input" name="name" value="{{ $term->name }}" style="max-width:260px;">
                    <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                </form>
            </td>
            <td class="mono">{{ $term->usage_count }}</td>
            <td>
                <form action="{{ route('admin.taxonomy.destroy', [$tab, $term->id]) }}" method="POST" onsubmit="return confirm('Delete this {{ rtrim($tabLabels[$tab], 's') }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="3" class="text-sub" style="text-align:center; padding:32px;">None yet — add your first {{ rtrim($tabLabels[$tab], 's') }} above.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
