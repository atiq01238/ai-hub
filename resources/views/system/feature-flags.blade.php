@extends('layouts.admin')
@section('title','Feature Flags')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
@php $enabled=$flags->where('is_enabled',true)->count(); $production=$flags->where('environment','production')->count(); $avg=$flags->count()?round($flags->avg('rollout_percentage')):0; @endphp
<div class="ad-page">
<x-page-header title="Feature Flags" subtitle="Control experimental releases and staged rollouts without pretending to be a full experimentation platform." :breadcrumb="['System','Feature Flags']" />
@if(session('status'))<div class="alert alert-success ad-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger ad-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
<section class="ad-kpis">
@foreach([['Total Flags',$flags->count(),'flag',''],['Enabled',$enabled,'toggle-right','green'],['Production',$production,'rocket','red'],['Average Rollout',$avg.'%','gauge','violet']] as [$label,$value,$icon,$tone])<article class="ad-kpi ad-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>@endforeach
</section>
<section class="card ad-flag-create">
<header class="ad-card-head"><div><span class="ad-eyebrow">Controlled Rollout</span><h2>Create feature flag</h2><p>Production flags require deliberate confirmation before state changes.</p></div><i data-lucide="flag"></i></header>
<form action="{{ route('admin.system.feature-flags.store') }}" method="POST" class="ad-flag-form">@csrf
<label><span>Name <b>*</b></span><input class="input" name="name" placeholder="e.g. AI Test Lab" required></label>
<label class="is-wide"><span>Description</span><input class="input" name="description" placeholder="What this flag controls"></label>
<label><span>Environment</span><select class="select" name="environment"><option value="staging">Staging</option><option value="production">Production</option></select></label>
<label><span>Rollout %</span><input class="input" type="number" name="rollout_percentage" value="0" min="0" max="100"></label>
<button class="btn btn-primary"><i data-lucide="plus"></i>Create Flag</button>
</form>
</section>
<section class="card ad-table-card"><header class="ad-card-head"><div><span class="ad-eyebrow">Release Controls</span><h2>Configured flags</h2><p>State and rollout percentage are stored independently.</p></div><span class="ad-count">{{ $flags->count() }} flags</span></header>
@if($flags->count())<div class="table-wrap"><table class="data-table ad-table"><thead><tr><th>Feature</th><th>Environment</th><th>Rollout</th><th>Created</th><th>State</th><th></th></tr></thead><tbody>
@foreach($flags as $flag)
<tr><td><div class="ad-feature"><span><i data-lucide="flag"></i></span><div><strong>{{ $flag->name }}</strong><small>{{ $flag->description ?: 'No description added.' }}</small></div></div></td><td><span class="ad-env {{ $flag->environment==='production'?'is-production':'' }}"><i data-lucide="{{ $flag->environment==='production'?'rocket':'flask-conical' }}"></i>{{ ucfirst($flag->environment) }}</span></td><td><div class="ad-rollout"><div><span style="width:{{ $flag->rollout_percentage }}%"></span></div><strong>{{ $flag->rollout_percentage }}%</strong></div></td><td><span class="ad-muted">{{ $flag->created_at->format('M j, Y') }}</span></td><td><form action="{{ route('admin.system.feature-flags.toggle',$flag->id) }}" method="POST" onsubmit="{{ $flag->environment==='production' ? "return confirm('This is a Production feature flag. Changing its state may affect live users. Continue?')" : '' }}">@csrf<button class="ad-switch-button" type="submit"><span class="ad-switch {{ $flag->is_enabled?'is-on':'' }}"><i></i></span><strong>{{ $flag->is_enabled?'ON':'OFF' }}</strong></button></form></td><td><form action="{{ route('admin.system.feature-flags.destroy',$flag->id) }}" method="POST" onsubmit="return confirm('Delete this feature flag?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger"><i data-lucide="trash-2"></i></button></form></td></tr>
@endforeach
</tbody></table></div>@else<div class="ad-empty"><span><i data-lucide="flag"></i></span><h3>No feature flags yet</h3><p>Create the first staged rollout control above.</p></div>@endif
</section></div>
@endsection
