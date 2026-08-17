@extends('layouts.admin')
@section('title','Taxonomy')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
@php
$labels=['categories'=>'Categories','subcategories'=>'Subcategories','features'=>'Features','tags'=>'Tags'];
$icons=['categories'=>'folder-tree','subcategories'=>'git-branch','features'=>'sparkles','tags'=>'tags'];
@endphp
<div class="fp-page">
<x-page-header title="AI Management Taxonomy" :subtitle="'Manage reusable '.$labels[$tab].' without breaking connected tool records.'" :breadcrumb="['AI Management',$labels[$tab]]" />
@if(session('status'))<div class="alert alert-success fp-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger fp-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
<nav class="fp-tabs">@foreach($labels as $key=>$label)<a href="{{ route('admin.taxonomy.'.$key) }}" class="{{ $tab===$key?'is-active':'' }}"><i data-lucide="{{ $icons[$key] }}"></i>{{ $label }}</a>@endforeach</nav>
<section class="fp-taxonomy-grid">
<aside class="card fp-taxonomy-create"><span class="fp-eyebrow">New {{ rtrim($labels[$tab],'s') }}</span><div class="fp-taxonomy-create__icon"><i data-lucide="{{ $icons[$tab] }}"></i></div><h2>Add taxonomy term</h2><p>Names are unique. Slugs are generated automatically.</p><form action="{{ route('admin.taxonomy.store',$tab) }}" method="POST">@csrf<label class="fp-field"><span>Name</span><input class="input" name="name" placeholder="New {{ rtrim($labels[$tab],'s') }} name" required></label><button class="btn btn-primary fp-full"><i data-lucide="plus"></i>Add Term</button></form><div class="fp-taxonomy-note"><i data-lucide="shield-check"></i><span>Renames synchronize connected tool data; feature/tag deletion detaches relationships without deleting tools.</span></div></aside>
<main class="card fp-table-card"><header class="fp-card-head"><div><span class="fp-eyebrow">{{ $labels[$tab] }}</span><h2>Taxonomy registry</h2><p>{{ $terms->count() }} reusable term(s) currently available.</p></div><span class="fp-count">{{ $terms->count() }}</span></header>
@if($terms->count())<div class="fp-taxonomy-list">@foreach($terms as $term)<article><span class="fp-taxonomy-term"><i data-lucide="{{ $icons[$tab] }}"></i></span><form method="POST" action="{{ route('admin.taxonomy.update',[$tab,$term->id]) }}" class="fp-taxonomy-edit">@csrf @method('PUT')<input class="input" name="name" value="{{ $term->name }}" required><span class="fp-usage"><i data-lucide="link-2"></i>{{ $term->usage_count }} uses</span><button class="btn btn-secondary btn-sm"><i data-lucide="save"></i>Save</button></form><form method="POST" action="{{ route('admin.taxonomy.destroy',[$tab,$term->id]) }}" onsubmit="return confirm('Delete this taxonomy term? Connected tools remain safe.')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger"><i data-lucide="trash-2"></i></button></form></article>@endforeach</div>@else<div class="fp-empty"><span><i data-lucide="{{ $icons[$tab] }}"></i></span><h3>No taxonomy terms yet</h3><p>Add the first reusable term from the panel on the left.</p></div>@endif</main>
</section></div>
@endsection
