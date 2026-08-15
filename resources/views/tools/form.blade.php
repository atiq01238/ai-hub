@extends('layouts.admin')
@section('title', isset($tool) ? 'Edit AI Tool' : 'Add AI Tool')
@section('content')
@php
    $tool ??= null;
    $old = fn($key,$default=null) => old($key, $tool->{$key} ?? $default);
    $selectedFeatures = old('feature_ids', $tool?->featureTerms?->pluck('id')->all() ?? []);
    $selectedTags = old('tag_ids', $tool?->tagTerms?->pluck('id')->all() ?? []);
@endphp
<form action="{{ $tool ? route('admin.tools.update',$tool->id) : route('admin.tools.store') }}" method="POST" enctype="multipart/form-data">@csrf @if($tool) @method('PUT') @endif
<x-page-header title="{{ $tool ? 'Edit AI Tool' : 'Add AI Tool' }}" :breadcrumb="['AI Management','AI Tools',$tool?'Edit':'Add']"><x-slot:actions><button class="btn btn-secondary btn-sm" name="status" value="draft">Save Draft</button><button class="btn btn-primary btn-sm" name="status" value="published">Publish</button></x-slot:actions></x-page-header>
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px;"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="grid-12"><div class="col-8">
<div class="card card-pad form-section" style="margin-bottom:16px;"><div class="form-section__title">Basic Information</div><div class="form-grid">
<div class="form-field"><label>Name</label><input class="input" name="name" required value="{{ $old('name') }}"></div>
<div class="form-field"><label>Slug</label><input class="input" name="slug" value="{{ $old('slug') }}" placeholder="Auto-generated if empty"></div>
<div class="form-field"><label>Company</label><select class="select" name="company_id"><option value="">No company</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected((string)$old('company_id')===(string)$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="form-field"><label>Category</label><select class="select" name="category_id"><option value="">No category</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((string)$old('category_id')===(string)$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="form-field"><label>Subcategory</label><select class="select" name="subcategory_id"><option value="">No subcategory</option>@foreach($subcategories as $s)<option value="{{ $s->id }}" @selected((string)$old('subcategory_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="form-field"><label>Website</label><input class="input" type="url" name="website" value="{{ $old('website') }}"></div>
<div class="form-field"><label>Launch Date</label><input class="input" type="date" name="launch_date" value="{{ $old('launch_date') ? \Illuminate\Support\Carbon::parse($old('launch_date'))->format('Y-m-d') : '' }}"></div>
<div class="form-field"><label>Short Description</label><input class="input" name="short_description" maxlength="255" value="{{ $old('short_description') }}"></div>
</div><div class="form-field" style="margin-top:12px;"><label>Description</label><textarea class="input" rows="6" name="description">{{ $old('description') }}</textarea></div></div>
<div class="card card-pad form-section" style="margin-bottom:16px;"><div class="form-section__title">Features / Capabilities</div><div class="flex gap-8" style="flex-wrap:wrap;">@forelse($features as $f)<label class="toggle-pill {{ in_array($f->id,$selectedFeatures) ? 'is-on':'' }}"><input type="checkbox" name="feature_ids[]" value="{{ $f->id }}" @checked(in_array($f->id,$selectedFeatures))> {{ $f->name }}</label>@empty<span class="text-sub">No features yet. Add them from AI Management → Features.</span>@endforelse</div></div>
<div class="card card-pad form-section"><div class="form-section__title">Tags</div><div class="flex gap-8" style="flex-wrap:wrap;">@forelse($tags as $tag)<label class="toggle-pill {{ in_array($tag->id,$selectedTags) ? 'is-on':'' }}"><input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array($tag->id,$selectedTags))> {{ $tag->name }}</label>@empty<span class="text-sub">No tags yet. Add them from AI Management → Tags.</span>@endforelse</div></div>
</div><div class="col-4">
<div class="card card-pad form-section" style="margin-bottom:16px;"><div class="form-section__title">Pricing & Platforms</div><div class="form-field"><label>Pricing Models</label>@php $pm=$old('pricing_models',[]); @endphp<div class="flex gap-8" style="flex-wrap:wrap;">@foreach(['Free','Freemium','Paid','Enterprise'] as $p)<label class="toggle-pill"><input type="checkbox" name="pricing_models[]" value="{{ $p }}" @checked(in_array($p,$pm??[]))> {{ $p }}</label>@endforeach</div></div><div class="form-field" style="margin-top:12px;"><label>Platforms</label>@php $pl=$old('platforms',[]); @endphp<div class="flex gap-8" style="flex-wrap:wrap;">@foreach(['Web','Windows','macOS','Linux','iOS','Android','API'] as $p)<label class="toggle-pill"><input type="checkbox" name="platforms[]" value="{{ $p }}" @checked(in_array($p,$pl??[]))> {{ $p }}</label>@endforeach</div></div></div>
<div class="card card-pad form-section" style="margin-bottom:16px;"><div class="form-section__title">Images</div><div class="form-field"><label>Logo</label><input class="input" type="file" name="logo" accept="image/*"></div><div class="form-field"><label>Cover Image</label><input class="input" type="file" name="cover_image" accept="image/*"></div><div class="form-field"><label>OG Image</label><input class="input" type="file" name="og_image" accept="image/*"></div></div>
<div class="card card-pad form-section"><div class="form-section__title">SEO</div><div class="form-field"><label>SEO Title</label><input class="input" name="seo_title" value="{{ $old('seo_title') }}"></div><div class="form-field"><label>Meta Description</label><textarea class="input" name="meta_description" rows="3">{{ $old('meta_description') }}</textarea></div></div>
</div></div></form>
@endsection
