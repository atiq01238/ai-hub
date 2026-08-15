@extends('layouts.admin')
@section('title', isset($model) ? 'Edit AI Model' : 'Add AI Model')
@section('content')
@php $model??=null; $old=fn($k,$d=null)=>old($k,$model->{$k}??$d); @endphp
<form action="{{ $model ? route('admin.models.update',$model->id) : route('admin.models.store') }}" method="POST">@csrf @if($model) @method('PUT') @endif
<x-page-header title="{{ $model?'Edit AI Model':'Add AI Model' }}" :breadcrumb="['AI Management','AI Models',$model?'Edit':'Add']"><x-slot:actions><button name="status" value="preview" class="btn btn-secondary btn-sm">Save Preview</button><button name="status" value="active" class="btn btn-primary btn-sm">Publish</button></x-slot:actions></x-page-header>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="grid-12"><div class="col-8"><div class="card card-pad form-section"><div class="form-section__title">Model Information</div><div class="form-grid">
<div class="form-field"><label>Model Name</label><input class="input" name="name" required value="{{ $old('name') }}"></div>
<div class="form-field"><label>Company</label><select class="select" id="company_id" name="company_id"><option value="">Select company...</option>@foreach($companies as $c)<option value="{{ $c->id }}" @selected((string)$old('company_id')===(string)$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="form-field"><label>Tool</label><select class="select" id="tool_id" name="tool_id"><option value="">Select tool...</option>@foreach($tools as $t)<option value="{{ $t->id }}" data-company="{{ $t->company_id }}" @selected((string)$old('tool_id')===(string)$t->id)>{{ $t->name }}</option>@endforeach</select><small class="text-sub">Tools are filtered to the selected company.</small></div>
<div class="form-field"><label>Version</label><input class="input" name="version" value="{{ $old('version') }}"></div>
<div class="form-field"><label>Release Date</label><input class="input" type="date" name="release_date" value="{{ $old('release_date') ? \Illuminate\Support\Carbon::parse($old('release_date'))->format('Y-m-d') : '' }}"></div>
<div class="form-field"><label>Context Window</label><input class="input" name="context_window" value="{{ $old('context_window') }}" placeholder="128K / 1M"></div>
<div class="form-field"><label>Input Price ($/1M)</label><input class="input" type="number" step="0.01" min="0" name="input_price_per_million" value="{{ $old('input_price_per_million') }}"></div>
<div class="form-field"><label>Output Price ($/1M)</label><input class="input" type="number" step="0.01" min="0" name="output_price_per_million" value="{{ $old('output_price_per_million') }}"></div>
<div class="form-field"><label>Benchmark Score</label><input class="input" type="number" step="0.1" min="0" max="100" name="benchmark_score" value="{{ $old('benchmark_score') }}"></div>
</div></div><div class="card card-pad form-section" style="margin-top:16px;"><div class="form-section__title">Capabilities</div>@php $caps=$old('capabilities',[]); @endphp<div class="flex gap-8" style="flex-wrap:wrap;">@foreach(['API Support','Reasoning','Vision','Audio','Image','Video'] as $c)<label class="toggle-pill"><input type="checkbox" name="capabilities[]" value="{{ $c }}" @checked(in_array($c,$caps??[]))> {{ $c }}</label>@endforeach</div><div class="form-field" style="margin-top:12px;"><label>Capability Notes</label><textarea class="input" rows="4" name="capability_notes">{{ $old('capability_notes') }}</textarea></div></div></div>
<div class="col-4 card card-pad"><div class="form-section__title">Model integrity</div><p class="text-sub">Slug is generated automatically and protected from duplicates. If a selected tool belongs to a company, the model company must match it.</p></div></div></form>
<script>
(function(){const company=document.getElementById('company_id'),tool=document.getElementById('tool_id');function filter(){const c=company.value;[...tool.options].forEach((o,i)=>{if(i===0)return;o.hidden=!!c && o.dataset.company && o.dataset.company!==c;});if(tool.selectedOptions[0]?.hidden)tool.value='';}company.addEventListener('change',filter);filter();})();
</script>
@endsection
