@extends('layouts.admin')
@section('title', 'Media Library')

@section('content')

<x-page-header title="Media Library" subtitle="4,208 files · 18.2 GB used" :breadcrumb="['Content', 'Media Library']">
    <x-slot:actions>
        <div class="flex gap-8" style="border:1px solid var(--border); border-radius:var(--radius-sm); overflow:hidden;">
            <button class="btn btn-secondary btn-sm" style="border-radius:0; border:none;"><i data-lucide="grid-2x2"></i></button>
            <button class="btn btn-ghost btn-sm" style="border-radius:0; border:none;"><i data-lucide="list"></i></button>
        </div>
        <button class="btn btn-primary btn-sm"><i data-lucide="upload"></i> Upload</button>
    </x-slot:actions>
</x-page-header>

<div class="grid-12" style="margin-bottom:16px;">
    <div class="col-4">
        <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
            <i data-lucide="search"></i><input type="text" placeholder="Search media...">
        </div>
    </div>
    <div class="col-8 filter-bar" style="margin:0; justify-content:flex-end;">
        @foreach(['Tool Logos','Tool Screenshots','News Images','Article Images','Review Media','Social Media','Videos'] as $i => $folder)
            <span class="chip {{ $i===0 ? 'is-active' : '' }}"><i data-lucide="folder" style="width:12px;height:12px;"></i> {{ $folder }}</span>
        @endforeach
    </div>
</div>

<div class="card card-pad">
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:14px;">
        @for($i = 1; $i <= 12; $i++)
        <div class="card" style="overflow:hidden; cursor:pointer;">
            <div class="thumb" style="width:100%; height:100px; border-radius:0; border:none;"><i data-lucide="image" style="width:22px;height:22px;"></i></div>
            <div style="padding:8px 10px;">
                <div style="font-size:11.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">tool-logo-{{ $i }}.png</div>
                <div class="cell-sub">240 KB</div>
            </div>
        </div>
        @endfor
    </div>
</div>
@endsection
