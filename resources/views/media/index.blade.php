@extends('layouts.admin')
@section('title', 'Media Library')

@section('content')

<x-page-header title="Media Library" subtitle="{{ $totalFiles }} files · {{ $totalSize }} used" :breadcrumb="['Content', 'Media Library']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->has('media'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first('media') }}</div>
@endif

<div class="grid-12" style="margin-bottom:16px;">
    <div class="col-4">
        <form method="GET">
            @if ($activeFolder)<input type="hidden" name="folder" value="{{ $activeFolder }}">@endif
            <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
                <i data-lucide="search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search media...">
            </div>
        </form>
    </div>
    <div class="col-8 filter-bar" style="margin:0; justify-content:flex-end;">
        <a href="{{ route('admin.media', ['search' => request('search')]) }}" class="chip {{ !$activeFolder ? 'is-active' : '' }}"><i data-lucide="folder" style="width:12px;height:12px;"></i> All</a>
        @foreach ($folders as $label => $folder)
            <a href="{{ route('admin.media', ['folder' => $folder, 'search' => request('search')]) }}" class="chip {{ $activeFolder === $folder ? 'is-active' : '' }}"><i data-lucide="folder" style="width:12px;height:12px;"></i> {{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="card card-pad">
    @if ($files->isEmpty())
        <div class="text-sub" style="text-align:center; padding:40px;">No files here yet — uploads from Tools, Companies, Articles, or Social Posts will appear here.</div>
    @else
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:14px;">
        @foreach ($files as $file)
        <div class="card" style="overflow:hidden;">
            <a href="{{ $file['url'] }}" target="_blank">
                <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" style="width:100%; height:100px; object-fit:cover; display:block;">
            </a>
            <div style="padding:8px 10px;">
                <div style="font-size:11.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                <div class="cell-sub">{{ $file['size'] < 1024 ? $file['size'].' B' : ($file['size'] < 1048576 ? round($file['size']/1024,1).' KB' : round($file['size']/1048576,1).' MB') }}</div>
                @if($file['usage_count'] > 0)<div class="badge badge-neutral" style="margin-top:5px;">In use: {{ implode(', ', $file['used_by']) }}</div>@else<div class="cell-sub" style="margin-top:5px;">Unused · safe to delete</div>@endif
                <form action="{{ route('admin.media.destroy') }}" method="POST" onsubmit="return confirm('Delete this unused file? Files still referenced by content are blocked automatically.')" style="margin-top:6px;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="path" value="{{ $file['path'] }}">
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%; justify-content:center;" {{ $file['usage_count'] > 0 ? 'disabled' : '' }}><i data-lucide="trash-2" style="width:12px;height:12px;"></i> Delete</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
